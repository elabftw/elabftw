<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;

class UsersHelperTest extends \PHPUnit\Framework\TestCase
{
    private UsersHelper $UsersHelper;

    protected function setUp(): void
    {
        $this->UsersHelper = new UsersHelper(1);
    }

    public function testHasExperiments(): void
    {
        $this->assertTrue($this->UsersHelper->hasExperiments());
    }

    public function testCountExperiments(): void
    {
        $this->assertIsInt($this->UsersHelper->countExperiments());
    }

    public function testCountTimestampedExperiments(): void
    {
        $this->assertIsInt($this->UsersHelper->countTimestampedExperiments());
    }

    public function testGetTeamsFromUserid(): void
    {
        $expected = array(array('id' => '1', 'name' => 'Alpha'));
        $this->assertEquals($expected, $this->UsersHelper->getTeamsFromUserid());
    }

    public function testGetTeamsFromNotFoundUserid(): void
    {
        $UsersHelper = new UsersHelper(1337);
        $this->expectException(ImproperActionException::class);
        $UsersHelper->getTeamsFromUserid();
    }

    public function testgetTeamsNameFromUserid(): void
    {
        $expected = array('Alpha');
        $this->assertEquals($expected, $this->UsersHelper->getTeamsNameFromUserid());
    }
}
