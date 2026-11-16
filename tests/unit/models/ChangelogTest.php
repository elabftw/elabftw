<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Traits\TestsUtilsTrait;

use function json_encode;

class ChangelogTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testCreate(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        $Experiments->setId($id);
        $body = 'initial body';
        $Experiments->patch(Action::Update, array('body' => $body));

        $Changelog = new Changelog($Experiments);
        $params = new EntityParams('body', $body);
        $this->assertFalse($Changelog->create($params));
    }

    public function testReadAllWithAbsoluteUrls(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        $Experiments->setId($id);
        $body = 'initial body';
        $Experiments->patch(Action::Update, array('body' => $body));
        $Changelog = new Changelog($Experiments);
        $this->assertIsArray($Changelog->readAllWithAbsoluteUrls());
    }

    public function testReplaceAll(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        $Experiments->setId($id);

        $Changelog = new Changelog($Experiments);

        // create an import-time changelog entry that should be removed.
        $Changelog->create(new EntityParams('title', 'Import generated title change'));
        $this->assertNotEmpty($Changelog->readAll());
        $Changelog->replaceAll(array(
            array(
                'created_at' => '2026-06-18 10:00:00',
                'target' => 'created',
                'content' => 'Experiment was created',
                'userid' => 1,
            ),
            array(
                'created_at' => '2026-06-18 11:00:00',
                'target' => 'locked',
                'content' => 'Locked',
                'userid' => 1,
            ),
        ));

        $changes = $Changelog->readAll();
        $this->assertCount(2, $changes);
        // readAll() orders DESC by created_at, so the locked row comes first.
        $this->assertSame('locked', $changes[0]['target']);
        $this->assertSame('Locked', $changes[0]['content']);
        $this->assertSame(1, (int) $changes[0]['userid']);
        $this->assertSame('2026-06-18 11:00:00', $changes[0]['created_at']);

        $this->assertSame('created', $changes[1]['target']);
        $this->assertSame('Experiment was created', $changes[1]['content']);
        $this->assertSame(1, (int) $changes[1]['userid']);
        $this->assertSame('2026-06-18 10:00:00', $changes[1]['created_at']);
    }

    public function testAnonymousUserCannotReadChangelog(): void
    {
        $Users = new Users(1, 1);
        $Experiments = $this->getFreshExperimentWithGivenUser($Users);
        $Experiments->patch(Action::Update, array('canread_base' => BasePermissions::User->value));
        $Experiments->patch(Action::Update, array('metadata' => '{"extra_fields":{"internal_note":{"type":"text","value":"SECRET-VALUE"}}}'));
        // the current value is scrubbed, only the history still holds the secret one
        $Experiments->patch(Action::Update, array('metadata' => '{"extra_fields":{"internal_note":{"type":"text","value":"innocent"}}}'));
        $Experiments->patch(Action::Update, array('canread_base' => BasePermissions::Full->value));
        // the owner still sees the full history, secret value included
        $this->assertStringContainsString('SECRET-VALUE', (string) json_encode(new Changelog($Experiments)->readAll()));

        $AnonExperiments = EntityType::Experiments->toInstance(new AnonymousUser(1));
        $AnonExperiments->setId((int) $Experiments->id);
        $this->assertEmpty(new Changelog($AnonExperiments)->readAll());
        $entry = $AnonExperiments->readOne();
        $this->assertSame(array(), $entry['changelog']);
        $this->assertStringNotContainsString('SECRET-VALUE', (string) json_encode($entry));
    }
}
