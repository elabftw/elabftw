<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\MissingRequiredKeyException;

class GuardTest extends \PHPUnit\Framework\TestCase
{
    private array $expectedKeys;

    protected function setUp(): void
    {
        $this->expectedKeys = array('userid', 'team');
    }

    public function testAllRequiredKeysPresent(): void
    {
        $input = array('userid' => 3, 'team' => 1);
        $this->assertSame($input, Guard::ensureRequiredKeysPresent($this->expectedKeys, $input));
    }

    public function testMissingRequiredKeyThrowsException(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        Guard::ensureRequiredKeysPresent(
            $this->expectedKeys,
            array('userid' => 3),
        );
    }

    public function testNullValueIsConsideredMissing(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        Guard::ensureRequiredKeysPresent(
            $this->expectedKeys,
            array('userid' => 3, 'team' => null),
        );
    }

    public function testMultipleMissingKeys(): void
    {
        try {
            Guard::ensureRequiredKeysPresent(
                $this->expectedKeys,
                array(),
            );
            $this->fail('Expected MissingRequiredKeyException was not thrown');
        } catch (MissingRequiredKeyException $e) {
            $this->assertStringContainsString('userid', $e->getMessage());
            $this->assertStringContainsString('team', $e->getMessage());
        }
    }

    public function testGetNonEmptyStringValueOfRequiredParamWithEmptyValue(): void
    {
        $this->assertSame('yep', Guard::getNonEmptyStringValueOfRequiredParam('k', array('k' => 'yep')));
        $this->expectException(ImproperActionException::class);
        Guard::getNonEmptyStringValueOfRequiredParam('key', array('key' => ''));
    }

    public function testEnsurePositiveInts(): void
    {
        $this->expectException(MissingRequiredKeyException::class);
        Guard::ensurePositiveInts(array('k', 'kk'), array('k' => 3, 'kk' => -12));
    }
}
