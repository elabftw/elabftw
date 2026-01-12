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
    public function testAllRequiredKeysPresent(): void
    {
        ApiParamsValidator::ensureRequiredKeysPresent(
            array('target_owner', 'target_team'),
            array('target_owner' => 3, 'target_team' => 1),
        );
        $this->assertTrue(true);
    }

    public function testMissingRequiredKeyThrowsException(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        ApiParamsValidator::ensureRequiredKeysPresent(
            array('target_owner', 'target_team'),
            array('target_owner' => 3),
        );
    }

    public function testNullValueIsConsideredMissing(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        ApiParamsValidator::ensureRequiredKeysPresent(
            array('target_owner', 'target_team'),
            array(
                'target_owner' => 3,
                'target_team' => null,
            ),
        );
    }

    public function testMultipleMissingKeys(): void
    {
        try {
            ApiParamsValidator::ensureRequiredKeysPresent(
                array('target_owner', 'target_team'),
                array(),
            );
            $this->fail('Expected MissingRequiredKeyException was not thrown');
        } catch (MissingRequiredKeyException $e) {
            $this->assertStringContainsString('target_owner', $e->getMessage());
            $this->assertStringContainsString('target_team', $e->getMessage());
        }
    }
}
