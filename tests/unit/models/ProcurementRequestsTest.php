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

class ProcurementRequestsTest extends \PHPUnit\Framework\TestCase
{
    private ProcurementRequests $pr;

    protected function setUp(): void
    {
        $this->pr = new ProcurementRequests(new Teams(new Users(1, 1), 1));
    }

    public function testCreate(): void
    {
        $entityId = 3;
        $id = $this->pr->postAction(Action::Create, array('entity_id' => $entityId, 'qty_ordered' => 1, 'body', 'quote' => 12));
        $this->assertIsInt($id);
        $this->pr->setId($id);
        $this->assertIsArray($this->pr->readOne());
        $this->assertIsArray($this->pr->readForEntity($entityId));
        $this->assertIsArray($this->pr->patch(Action::Update, array('qty_received' => 2)));
    }

    public function testRead(): void
    {
        $res = $this->pr->readAll();
        $this->assertIsArray($res);
        $this->assertNotEmpty($res);
        $this->assertIsString($res[0]['state_human']);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/teams/current/procurement_requests/', $this->pr->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->pr->destroy());
    }
}
