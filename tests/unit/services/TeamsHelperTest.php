<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class TeamsHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->TeamsHelper = new TeamsHelper(1);
    }

    public function testGetGroup()
    {
        $this->assertEquals(4, $this->TeamsHelper->getGroup());
    }
}
