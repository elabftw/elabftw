<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

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
        $this->assertEquals(58, $this->UsersHelper->countExperiments());
    }

    public function testCountTimestampedExperiments(): void
    {
        $this->assertEquals(0, $this->UsersHelper->countTimestampedExperiments());
    }

    public function testGetTeamsFromUserid(): void
    {
        $expected = array(array('id' => '1', 'name' => 'Alpha'));
        $this->assertEquals($expected, $this->UsersHelper->getTeamsFromUserid());
    }

    public function testGetPermissions(): void
    {
        $expected = array('is_admin' => '1', 'is_sysadmin' => '1', 'can_lock' => '0');
        $this->assertEquals($expected, $this->UsersHelper->getPermissions());
    }

    public function testgetTeamsNameFromUserid(): void
    {
        $expected = array('Alpha');
        $this->assertEquals($expected, $this->UsersHelper->getTeamsNameFromUserid());
    }
}
