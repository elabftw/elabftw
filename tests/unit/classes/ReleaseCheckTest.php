<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Config;

class ReleaseCheckTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->ReleaseCheck = new ReleaseCheck(new Config());
    }

    public function testgetUpdatesIni()
    {
        $this->ReleaseCheck->getUpdatesIni();
    }

    public function testUpdateIsAvailable()
    {
        $this->assertIsBool($this->ReleaseCheck->updateIsAvailable());
    }
}
