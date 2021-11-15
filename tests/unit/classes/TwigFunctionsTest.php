<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class TwigFunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetLimitOptions(): void
    {
        $this->assertEquals(2, TwigFunctions::getLimitOptions(2)[0]);
        $this->assertEquals(10, TwigFunctions::getLimitOptions(10)[0]);
        $this->assertEquals(12, TwigFunctions::getLimitOptions(12)[1]);
        $this->assertEquals(52, TwigFunctions::getLimitOptions(52)[3]);
    }

    public function testGetGenerationTime(): void
    {
        $this->assertIsFloat(TwigFunctions::getGenerationTime());
    }

    public function testGetMemoryUsage(): void
    {
        $this->assertIsInt(TwigFunctions::getMemoryUsage());
    }

    public function testGetNumberOfQueries(): void
    {
        $this->assertIsInt(TwigFunctions::getNumberOfQueries());
    }

    public function testGetMinPasswordLength(): void
    {
        $this->assertIsInt(TwigFunctions::getMinPasswordLength());
    }
}
