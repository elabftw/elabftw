<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Elabftw\NullLocalPassword;
use Elabftw\Enums\Action;
use Elabftw\Enums\AuditCategory;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Items;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\UnprocessableContentException;

use function array_diff;
use function dirname;
use function sprintf;
use function uniqid;

class HandlerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Handler $handler;

    protected function setUp(): void
    {
        $this->handler = new Handler(new Users(1, 1), new ConsoleLogger(new NullOutput()));
    }

    public function testRead(): void
    {
        $res = $this->handler->readAll();
        $this->assertEquals(209715200, $res['max_filesize']);
    }

    public function testPostCsv(): void
    {
        // remember the items already owned by user 2 so we can identify newly imported ones
        $Items = new Items(new Users(1, 1));
        $itemsBeforeImport = $Items->getIdFromUser(2);
        $this->assertEquals(13, $this->handler->postAction(Action::Update, $this->getCsvRequest(2)));
        // import should have created exactly 13 new items owned by the selected owner.
        $importedItems = array_diff($Items->getIdFromUser(2), $itemsBeforeImport);
        $this->assertCount(13, $importedItems);

        // Verify that ownership was assigned to requested owner (userid = 2)
        foreach ($importedItems as $itemId) {
            $this->assertEquals(2, new Items(new Users(2, 1), (int) $itemId)->readOne()['userid']);
        }
        // and the audit log must still identify the authenticated requester, not the selected owner
        $auditLog = AuditLogs::read(1)[0];
        $this->assertEquals(AuditCategory::Import->value, $auditLog['category']);
        $this->assertEquals(1, $auditLog['requester_userid']);
    }

    public function testNonAdminCannotSelectAnotherOwner(): void
    {
        $handler = new Handler(new Users(2, 1), new ConsoleLogger(new NullOutput()));
        $this->expectException(ForbiddenException::class);
        $handler->postAction(Action::Update, $this->getCsvRequest(1));
    }

    public function testPostCsvWithoutOwner(): void
    {
        $req = $this->getCsvRequest(1);
        unset($req['owner']);
        $this->assertEquals(13, $this->handler->postAction(Action::Update, $req));
    }

    public function testPostCsvWithOwnerZero(): void
    {
        $this->assertEquals(13, $this->handler->postAction(Action::Update, $this->getCsvRequest(0)));
    }

    public function testCannotSelectOwnerOutsideDestinationTeam(): void
    {
        // create a regular user that belongs only to team 2
        $outsideOwner = new Users(1, 1)->createOne(
            email: sprintf('import-outside-team-%s@example.com', uniqid()),
            teams: array(2),
            localPassword: new NullLocalPassword(),
            usergroup: Usergroup::User,
            automaticValidationEnabled: true,
            alertAdmin: false,
            skipDomainValidation: true,
        );

        $this->expectException(UnprocessableContentException::class);
        // The owner cannot be selected because they are not a member of the destination team
        $this->handler->postAction(Action::Update, $this->getCsvRequest($outsideOwner));
    }

    public function testPostEln(): void
    {
        $req = array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/multiple-experiments.eln',
                'importable.eln',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'experiments',
            'owner' => 2,
        );
        $this->assertEquals(9, $this->handler->postAction(Action::Update, $req));
    }

    public function testPostInvalidExtension(): void
    {
        $req = array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/importable-chem.csv',
                'nope.zip',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'items',
        );
        $this->expectException(ImproperActionException::class);
        $this->handler->postAction(Action::Update, $req);
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->handler->patch(Action::Update, array());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/import/', $this->handler->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->handler->destroy();
    }

    private function getCsvRequest(int $owner): array
    {
        return array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/importable-chem.csv',
                'importable.csv',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'items',
            'owner' => $owner,
            'template' => 1,
        );
    }
}
