<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;

class UserUploadsTest extends \PHPUnit\Framework\TestCase
{
    private UserUploads $UserUploads;

    protected function setUp(): void
    {
        $this->UserUploads = new UserUploads(new Users(1, 1));
    }

    public function testPostAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->UserUploads->postAction(Action::Create, array());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/user/1/uploads/', $this->UserUploads->getApiPath());
    }

    public function testCountAll(): void
    {
        $Db = Db::getConnection();
        $Db->beginTransaction();

        try {
            $normalQuery = $this->UserUploads->getQueryParams(
                new InputBag(array(
                    'state' => State::Normal->value,
                )),
            );
            $archivedQuery = $this->UserUploads->getQueryParams(
                new InputBag(array(
                    'state' => State::Archived->value,
                )),
            );

            $normalBefore = $this->UserUploads->countAll($normalQuery);
            $archivedBefore = $this->UserUploads->countAll($archivedQuery);

            $sql = 'INSERT INTO uploads
                (real_name, long_name, userid, type, state)
                VALUES
                (:normal_name, :normal_long_name, 1, :type, :normal_state),
                (:archived_name, :archived_long_name, 1, :type, :archived_state)';

            $req = $Db->prepare($sql);
            $req->bindValue(':normal_name', 'useruploads-normal.txt');
            $req->bindValue(':normal_long_name', 'test/useruploads-normal.txt');
            $req->bindValue(':archived_name', 'useruploads-archived.txt');
            $req->bindValue(':archived_long_name', 'test/useruploads-archived.txt');
            $req->bindValue(':type', 'experiments');
            $req->bindValue(':normal_state', State::Normal->value, PDO::PARAM_INT);
            $req->bindValue(':archived_state', State::Archived->value, PDO::PARAM_INT);
            $Db->execute($req);

            $this->assertSame(
                $normalBefore + 1,
                $this->UserUploads->countAll($normalQuery),
            );
            $this->assertSame(
                $archivedBefore + 1,
                $this->UserUploads->countAll($archivedQuery),
            );
        } finally {
            $Db->rollBack();
        }
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->UserUploads->readOne());
        $UserUploads = new UserUploads(new Users(1, 1), 1);
        $res = $UserUploads->readOne();
        $this->assertIsArray($res);
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->UserUploads->patch(Action::Lock, array());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->UserUploads->destroy();
    }
}
