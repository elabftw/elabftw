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
use Elabftw\Enums\ProcurementState;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;

class ProcurementRequestsTest extends \PHPUnit\Framework\TestCase
{
    private ProcurementRequests $pr;

    private ProcurementRequests $otherTeamPr;

    protected function setUp(): void
    {
        $this->pr = new ProcurementRequests(new Teams(new Users(1, 1), 1));
        $this->otherTeamPr = new ProcurementRequests(new Teams(new Users(2, 2), 2));
    }

    public function testCreate(): void
    {
        $entityId = 3;
        $id = $this->pr->postAction(Action::Create, array('entity_id' => $entityId, 'qty_ordered' => 1, 'body' => '', 'quote' => 12));
        $this->assertIsInt($id);
        $this->pr->setId($id);
        $this->assertIsArray($this->pr->readOne());
        $this->assertIsArray($this->pr->readActiveForEntity($entityId));
        $this->assertNotEmpty($this->pr->readActiveForEntity($entityId));
        $this->assertIsArray($this->pr->patch(Action::Update, array('qty_received' => 2)));
    }

    public function testRead(): void
    {
        $res = $this->pr->readAll();
        $this->assertIsArray($res);
        $this->assertNotEmpty($res);
        $this->assertIsString($res[0]['state_human']);
    }

    public function testReadRecordNotFound(): void
    {
        $this->pr->setId(2);
        $this->expectException(ResourceNotFoundException::class);
        $this->pr->readOne();
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/teams/current/procurement_requests/', $this->pr->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->pr->setId(1);
        $this->assertTrue($this->pr->destroy());
        $this->assertEquals(ProcurementState::Cancelled->value, $this->pr->readOne()['state']);
    }

    public function testUpdateRecordNotFound(): void
    {
        $this->pr->setId(2);
        $this->expectException(ResourceNotFoundException::class);
        $this->pr->patch(Action::Update, array('qty_received' => 2));
    }

    public function testReadNonAccessibleRecord(): void
    {
        $this->otherTeamPr->setId(1);
        $this->expectException(ResourceNotFoundException::class);
        $this->otherTeamPr->readOne();
    }

    public function testUpdateNonAccessibleRecord(): void
    {
        $this->otherTeamPr->setId(1);
        $this->expectException(ImproperActionException::class);
        $this->otherTeamPr->patch(Action::Update, array('qty_received' => 2));
    }
}
