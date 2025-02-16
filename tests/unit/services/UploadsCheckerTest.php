<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Symfony\Component\Console\Output\ConsoleOutput;

class UploadsCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testFix(): void
    {
        $UploadsChecker = new UploadsChecker(new ConsoleOutput());
        $this->assertEquals(0, $UploadsChecker->fixNullFilesize());
        $this->assertEquals(0, $UploadsChecker->fixNullHash());
    }

    public function testGetStats(): void
    {
        $this->assertIsArray(UploadsChecker::getStats());
    }
}
