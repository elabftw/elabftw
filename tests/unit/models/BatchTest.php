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

class BatchTest extends \PHPUnit\Framework\TestCase
{
    private Batch $Batch;

    protected function setUp(): void
    {
        $this->Batch = new Batch(new Users(1, 1));
    }

    public function testPostAction(): void
    {
        $reqBody = array(
            'action' => Action::ForceUnlock->value,
            'items_types' => array(1, 2),
            'items_status' => array(1, 2),
            'experiments_categories' => array(1, 2),
            'experiments_status' => array(1, 2),
            'tags' => array(1, 2),
            'users' => array(1, 2),
        );
        $this->assertIsInt($this->Batch->postAction(Action::Create, $reqBody));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/', $this->Batch->getApiPath());
    }

    public function testRead(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Batch->readOne();
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Batch->patch(Action::Lock, array());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Batch->destroy();
    }
}
