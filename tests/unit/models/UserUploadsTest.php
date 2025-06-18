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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
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
        // count All without filter (normal, archived, deleted)
        $countAll = $this->UserUploads->countAll();
        $this->assertIsInt($this->UserUploads->countAll());
        // count only archived
        $queryParams = new InputBag(array('state' => 2));
        $q = $this->UserUploads->getQueryParams($queryParams);
        $countArchived = $this->UserUploads->countAll($q);
        $this->assertIsInt($countArchived);
        $this->assertNotEquals($countAll, $countArchived, 'Total count and archived count should differ');
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
