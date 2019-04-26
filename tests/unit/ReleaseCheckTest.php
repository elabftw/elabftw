<?php
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
