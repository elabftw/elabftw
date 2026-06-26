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
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;

class ChangelogTest extends \PHPUnit\Framework\TestCase
{
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
}
