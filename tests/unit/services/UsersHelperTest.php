<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\Usergroup;

class UsersHelperTest extends \PHPUnit\Framework\TestCase
{
    private UsersHelper $UsersHelper;

    protected function setUp(): void
    {
        $this->UsersHelper = new UsersHelper(1);
    }

    public function testCannotBeDeleted(): void
    {
        $this->assertTrue($this->UsersHelper->cannotBeDeleted());
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
        $expected = array(array('id' => 1, 'name' => 'Alpha', 'usergroup' => Usergroup::Admin->value, 'is_owner' => 0));
        $this->assertEquals($expected, $this->UsersHelper->getTeamsFromUserid());
    }

    public function testGetTeamsFromNotFoundUserid(): void
    {
        $UsersHelper = new UsersHelper(1337);
        $this->assertEmpty($UsersHelper->getTeamsFromUserid());
    }

    public function testgetTeamsNameFromUserid(): void
    {
        $expected = array('Alpha');
        $this->assertEquals($expected, $this->UsersHelper->getTeamsNameFromUserid());
    }
}
