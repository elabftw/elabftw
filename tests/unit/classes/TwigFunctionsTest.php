<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\BasePermissions;

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

    public function testGetExtendedSearchExample(): void
    {
        $this->assertIsString(TwigFunctions::getExtendedSearchExample());
    }

    public function testGetNumberOfQueries(): void
    {
        $this->assertIsInt(TwigFunctions::getNumberOfQueries());
    }

    public function testToDatetime(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', TwigFunctions::toDatetime('2023-02-01'));
    }

    public function testExtractJson(): void
    {
        $json = BasePermissions::Organization->toJson();
        $key = 'base';
        $this->assertEquals(BasePermissions::Organization->value, TwigFunctions::extractJson($json, $key));
        $this->assertFalse(TwigFunctions::extractJson($json, 'unknown_key'));
    }

    public function testIsInJsonArray(): void
    {
        $json = '{"my_arr": [ 4, 5, 6 ]}';
        $key = 'my_arr';
        $this->assertTrue(TwigFunctions::isInJsonArray($json, $key, 5));
        $this->assertFalse(TwigFunctions::isInJsonArray($json, $key, 7));
    }

    public function testCanToHuman(): void
    {
        $this->assertIsArray(TwigFunctions::canToHuman(BasePermissions::User->toJson()));
    }
}
