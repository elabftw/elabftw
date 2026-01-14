<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\MissingRequiredKeyException;

class MissingRequiredKeyValidatorTest extends \PHPUnit\Framework\TestCase
{
    private array $expectedKeys;

    protected function setUp(): void
    {
        $this->expectedKeys = array('userid', 'team');
    }

    public function testAllRequiredKeysPresent(): void
    {
        ApiParamsValidator::ensureRequiredKeysPresent(
            $this->expectedKeys,
            array('userid' => 3, 'team' => 1),
        );
        $this->assertTrue(true);
    }

    public function testMissingRequiredKeyThrowsException(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        ApiParamsValidator::ensureRequiredKeysPresent(
            $this->expectedKeys,
            array('userid' => 3),
        );
    }

    public function testNullValueIsConsideredMissing(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        ApiParamsValidator::ensureRequiredKeysPresent(
            $this->expectedKeys,
            array('userid' => 3, 'team' => null),
        );
    }

    public function testMultipleMissingKeys(): void
    {
        try {
            ApiParamsValidator::ensureRequiredKeysPresent(
                $this->expectedKeys,
                array(),
            );
            $this->fail('Expected MissingRequiredKeyException was not thrown');
        } catch (MissingRequiredKeyException $e) {
            $this->assertStringContainsString('userid', $e->getMessage());
            $this->assertStringContainsString('team', $e->getMessage());
        }
    }
}
