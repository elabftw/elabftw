<?php

/**
 * @copyright 2026 Nicolas CARPi / Deltablot
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Codeception\Attribute\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;

use function basename;
use function json_encode;
use function mb_substr;

final class UploadDownloadValidatorTest extends TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Users $owner;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->owner = $this->getUserInTeam(1);
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    /**
     * @param 'owner'|'admin'|'other-team'|'anonymous' $role
     */
    #[DataProvider('downloadPermissions')]
    public function testParentReadPermissions(EntityType $type, BasePermissions $base, string $role, bool $allowed): void
    {
        $upload = $this->createUpload($type, $base);
        $requester = match ($role) {
            'owner' => $this->owner,
            'admin' => $this->getUserInTeam(1, 1),
            'other-team' => $this->getUserInTeam(2),
            'anonymous' => new AnonymousUser(1),
        };
        if (!$allowed) {
            $this->expectException(ForbiddenException::class);
        }
        new UploadDownloadValidator()->validate($requester, $upload['long_name'], Storage::LOCAL->value);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('entityTypes')]
    public function testRegularUserCannotReadAnotherUsersPrivateAttachment(EntityType $type): void
    {
        $upload = $this->createUpload($type, owner: $this->getUserInTeam(1, 1));
        $this->expectException(ForbiddenException::class);
        new UploadDownloadValidator()->validate($this->owner, $upload['long_name'], Storage::LOCAL->value);
    }

    #[DataProvider('entityTypes')]
    public function testThumbnailsAndLegacyNamesUseParentPermissions(EntityType $type): void
    {
        foreach (array(false, true) as $legacy) {
            $upload = $this->createUpload($type, legacy: $legacy);
            foreach (array($upload['long_name'], basename($upload['long_name'])) as $name) {
                foreach (array($name, $name . '_th.jpg', $name . '_TH.JPG') as $path) {
                    new UploadDownloadValidator()->validate($this->owner, $path, Storage::LOCAL->value);
                    try {
                        new UploadDownloadValidator()->validate(new AnonymousUser(1), $path, Storage::LOCAL->value);
                        $this->fail('An anonymous user downloaded a private attachment or thumbnail.');
                    } catch (ForbiddenException) {
                        $this->addToAssertionCount(1);
                    }
                }
            }
        }
    }

    #[DataProvider('entityTypes')]
    public function testExplicitSharesAllowAnotherTeam(EntityType $type): void
    {
        $requester = $this->getUserInTeam(2);
        foreach (array('users' => $requester->userid, 'teams' => 2) as $key => $id) {
            $upload = $this->createUpload($type, canread: json_encode(array($key => array($id)), JSON_THROW_ON_ERROR));
            new UploadDownloadValidator()->validate($requester, $upload['long_name'], Storage::LOCAL->value);
            $this->addToAssertionCount(1);
        }
    }

    #[DataProvider('entityTypes')]
    public function testAccessKeyMustBelongToTheAttachmentParent(EntityType $type): void
    {
        $upload = $this->createUpload($type);
        $accessKey = new AccessKeyHelper($type, $upload['item_id'])->toggleAccessKey();
        $requester = new AnonymousUser(1);
        $validator = new UploadDownloadValidator();
        $validator->validate($requester, $upload['long_name'], Storage::LOCAL->value, $accessKey);
        $validator->validate($requester, $upload['long_name'] . '_th.jpg', Storage::LOCAL->value, $accessKey);

        $otherUpload = $this->createUpload($type);
        $this->expectException(ForbiddenException::class);
        $validator->validate($requester, $otherUpload['long_name'], Storage::LOCAL->value, $accessKey);
    }

    #[DataProvider('entityTypes')]
    public function testRevokedAccessKeyNoLongerAllowsDownload(EntityType $type): void
    {
        $upload = $this->createUpload($type);
        $helper = new AccessKeyHelper($type, $upload['item_id']);
        $accessKey = $helper->toggleAccessKey();
        $helper->toggleAccessKey();
        $this->expectException(ForbiddenException::class);
        new UploadDownloadValidator()->validate(new AnonymousUser(1), $upload['long_name'], Storage::LOCAL->value, $accessKey);
    }

    #[DataProvider('entityTypes')]
    public function testChangedPermissionsAreCheckedAgain(EntityType $type): void
    {
        $upload = $this->createUpload($type, BasePermissions::Full);
        $requester = new AnonymousUser(1);
        $validator = new UploadDownloadValidator();
        $validator->validate($requester, $upload['long_name'], Storage::LOCAL->value);
        $req = $this->Db->prepare('UPDATE ' . $type->value . ' SET canread_base = :base WHERE id = :id');
        $req->bindValue(':base', BasePermissions::UserOnly->value, PDO::PARAM_INT);
        $req->bindValue(':id', $upload['item_id'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->expectException(ForbiddenException::class);
        $validator->validate($requester, $upload['long_name'], Storage::LOCAL->value);
    }

    public function testRejectsUnknownFiles(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        new UploadDownloadValidator()->validate($this->owner, Tools::getUuidv4() . '.txt', Storage::LOCAL->value);
    }

    public function testNonUploadFilesAreAllowedWithoutRequester(): void
    {
        $before = $this->Db->getNumberOfQueries();
        new UploadDownloadValidator()->validate(null, Tools::getUuidv4() . '.zip');
        $this->assertSame(1, $this->Db->getNumberOfQueries() - $before);
    }

    public function testUploadsRequireRequesterEvenWhenPublic(): void
    {
        $upload = $this->createUpload(EntityType::Experiments, BasePermissions::Full);
        $this->expectException(ForbiddenException::class);
        new UploadDownloadValidator()->validate(null, $upload['long_name']);
    }

    public function testStorageCannotBeChangedToAnotherBackend(): void
    {
        $upload = $this->createUpload(EntityType::Experiments);
        $this->expectException(ResourceNotFoundException::class);
        new UploadDownloadValidator()->validate($this->owner, $upload['long_name'], Storage::EXPORTS->value);
    }

    #[DataProvider('entityTypes')]
    public function testArchivedAttachmentsOnS3RemainReadable(EntityType $type): void
    {
        $upload = $this->createUpload($type);
        $req = $this->Db->prepare('UPDATE uploads SET storage = :storage, state = :state WHERE long_name = :long_name');
        $req->bindValue(':storage', Storage::S3->value, PDO::PARAM_INT);
        $req->bindValue(':state', State::Archived->value, PDO::PARAM_INT);
        $req->bindValue(':long_name', $upload['long_name']);
        $this->Db->execute($req);
        new UploadDownloadValidator()->validate($this->owner, $upload['long_name'], Storage::S3->value);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('entityTypes')]
    public function testDeletedParentIsRejectedEvenWithAccessKey(EntityType $type): void
    {
        $upload = $this->createUpload($type, BasePermissions::Full);
        $accessKey = new AccessKeyHelper($type, $upload['item_id'])->toggleAccessKey();
        $req = $this->Db->prepare('UPDATE ' . $type->value . ' SET state = :state WHERE id = :id');
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':id', $upload['item_id'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->expectException(ResourceNotFoundException::class);
        new UploadDownloadValidator()->validate($this->owner, $upload['long_name'], Storage::LOCAL->value, $accessKey);
    }

    public function testDeletedAttachmentIsRejected(): void
    {
        $upload = $this->createUpload(EntityType::Experiments);
        $req = $this->Db->prepare('UPDATE uploads SET state = :state WHERE long_name = :long_name');
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':long_name', $upload['long_name']);
        $this->Db->execute($req);
        $this->expectException(ResourceNotFoundException::class);
        new UploadDownloadValidator()->validate($this->owner, $upload['long_name'] . '_th.jpg', Storage::LOCAL->value);
    }

    public static function downloadPermissions(): array
    {
        $cases = array();
        foreach (EntityType::cases() as $type) {
            foreach (BasePermissions::cases() as $base) {
                $roles = array(
                    'owner' => true,
                    'admin' => $base !== BasePermissions::UserOnly,
                    'other-team' => $base === BasePermissions::Full || $base === BasePermissions::Organization,
                    'anonymous' => $base === BasePermissions::Full,
                );
                foreach ($roles as $role => $allowed) {
                    $cases[$type->value . '/' . $base->name . '/' . $role] = array($type, $base, $role, $allowed);
                }
            }
        }
        return $cases;
    }

    private function createUpload(
        EntityType $type,
        BasePermissions $base = BasePermissions::UserOnly,
        bool $legacy = false,
        string $canread = AbstractEntity::EMPTY_CAN_JSON,
        ?Users $owner = null,
    ): array {
        $owner ??= $this->owner;
        $Entity = $type->toInstance($owner);
        $id = $Entity->create(canreadBase: $base, canwriteBase: BasePermissions::UserOnly, canread: $canread);
        $name = Tools::getUuidv4() . '.txt';
        $longName = $legacy ? $name : mb_substr($name, 0, 2) . '/' . $name;
        $req = $this->Db->prepare('INSERT INTO uploads (real_name, long_name, item_id, userid, type, storage, state)
            VALUES (:real_name, :long_name, :item_id, :userid, :type, :storage, :state)');
        $req->bindValue(':real_name', 'permissions.txt');
        $req->bindValue(':long_name', $longName);
        $req->bindValue(':item_id', $id, PDO::PARAM_INT);
        $req->bindValue(':userid', $owner->userid, PDO::PARAM_INT);
        $req->bindValue(':type', $type->value);
        $req->bindValue(':storage', Storage::LOCAL->value, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array('long_name' => $longName, 'item_id' => $id);
    }
}
