<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function json_encode;
use function sprintf;

/**
 * The access key gives anonymous read access to an entry: it must only be shown to those who can toggle it,
 * and it must stop working when who can access the entry changes
 */
final class AccessKeyTest extends TestCase
{
    use TestsUtilsTrait;

    #[DataProvider('concreteEntityTypes')]
    public function testAccessKeyIsHiddenFromReadOnlyUsers(EntityType $type): void
    {
        $Owner = $this->getRegularUserInTeam(1);
        $Reader = $this->getRegularUserInTeam(1, array($Owner->getUserid()));
        $Entity = $this->getFreshEntityWithGivenUser($type, $Owner);
        $Entity->patch(Action::Update, array('canread_base' => BasePermissions::Team->value, 'canwrite_base' => BasePermissions::User->value));
        $ak = new AccessKeyHelper($type, $Entity->id)->toggleAccessKey();
        $this->assertNotNull($ak);

        // the owner can toggle the key, so sees it
        $ownerView = $Entity->readOne();
        $this->assertSame($ak, $ownerView['access_key']);
        $this->assertStringContainsString($ak, $ownerView['sharelink']);

        // a team member who can only read the entry does not
        $ReaderEntity = $type->toInstance($Reader, $Entity->id);
        $readerView = $ReaderEntity->readOne();
        $this->assertNull($readerView['access_key']);
        $this->assertStringNotContainsString($ak, $readerView['sharelink']);
        $this->assertStringNotContainsString($ak, (string) json_encode($ReaderEntity->readAll()));
    }

    #[DataProvider('concreteEntityTypes')]
    public function testAccessKeyIsRotatedWhenAccessChanges(EntityType $type): void
    {
        $Owner = $this->getRegularUserInTeam(1);
        $Entity = $this->getFreshEntityWithGivenUser($type, $Owner, BasePermissions::Full);
        $Helper = new AccessKeyHelper($type, $Entity->id);
        $ak = $Helper->toggleAccessKey();
        $this->assertNotNull($ak);

        // saving the same permissions keeps the link working
        $Entity->patch(Action::Update, array('canread_base' => BasePermissions::Full->value));
        $this->assertSame($Entity->id, $Helper->getIdFromAccessKey($ak));

        // tightening the read permissions
        $ak = $this->assertRotated($Entity, $Helper, $ak, array('canread_base' => BasePermissions::UserOnly->value));
        // changing the list of who can read
        $ak = $this->assertRotated($Entity, $Helper, $ak, array('canread' => sprintf('{"teams": [], "teamgroups": [], "users": [%d]}', $Owner->getUserid())));
        // changing who can write
        $this->assertRotated($Entity, $Helper, $ak, array('canwrite_base' => BasePermissions::UserOnly->value));
    }

    #[DataProvider('concreteEntityTypes')]
    public function testDisabledAccessKeyIsNotEnabledByRotation(EntityType $type): void
    {
        $Owner = $this->getRegularUserInTeam(1);
        $Entity = $this->getFreshEntityWithGivenUser($type, $Owner, BasePermissions::Full);
        $Entity->patch(Action::Update, array('canread_base' => BasePermissions::UserOnly->value));
        $this->assertNull($Entity->readOne()['access_key']);
    }

    private function assertRotated(AbstractEntity $Entity, AccessKeyHelper $Helper, string $oldAk, array $params): string
    {
        $Entity->patch(Action::Update, $params);
        $this->assertSame(0, $Helper->getIdFromAccessKey($oldAk));
        $newAk = $Entity->readOne()['access_key'];
        $this->assertIsString($newAk);
        $this->assertNotSame($oldAk, $newAk);
        $this->assertSame($Entity->id, $Helper->getIdFromAccessKey($newAk));
        return $newAk;
    }
}
