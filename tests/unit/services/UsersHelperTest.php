<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class UsersHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->UsersHelper = new UsersHelper();
    }

    public function testHasExperiments()
    {
        $this->assertTrue($this->UsersHelper->hasExperiments(1));
    }

    public function testGetGroup()
    {
        $this->assertEquals(4, $this->UsersHelper->getGroup(1));
    }
}
