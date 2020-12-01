<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Status = new Status(new Users(1, 1));
    }

    public function testCreate()
    {
        $new = $this->Status->create(
            new ParamsProcessor(
                array(
                    'name' => 'New status',
                    'color' => '#29AEB9',
                    'isTimestampable' => 0,
                    'isDefault' => 1,
                )
            )
        );
        $this->assertTrue((bool) Check::id($new));
    }

    public function testRead()
    {
        $all = $this->Status->read();
        $this->assertTrue(is_array($all));
    }

    public function testUpdate()
    {
        $params = new ParamsProcessor(
            array(
                'name' => 'Yep',
                'color' => '#29AEB9',
                'isTimestampable' => 0,
                'isDefault' => 1,
            )
        );
        $id = $this->Status->create($params);
        $params->id = $id;
        $params->isTimestampable = 1;
        $this->Status->update($params);
        $this->assertTrue($this->Status->isTimestampable($id));
    }

    public function testReadColor()
    {
        $this->assertEquals('29aeb9', strtolower($this->Status->readColor(1)));
    }

    public function testIsTimestampable()
    {
        $this->assertFalse($this->Status->isTimestampable(1));
    }

    public function testDestroy()
    {
        //$this->Status->destroy(2);
        $this->expectException(ImproperActionException::class);
        $this->Status->destroy(1);
    }
}
