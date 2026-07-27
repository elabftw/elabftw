<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;

use function array_fill;

class OpenBabelFingerprinterTest extends \PHPUnit\Framework\TestCase
{
    public function testCalculate(): void
    {
        $Fingerprinter = new OpenBabelFingerprinter();

        $fingerprint = $Fingerprinter->calculate('smi', 'CCO');

        $this->assertArrayHasKey('data', $fingerprint);
        $this->assertCount(32, $fingerprint['data']);
        foreach ($fingerprint['data'] as $word) {
            $this->assertIsInt($word);
        }
        $this->assertNotSame(array_fill(0, 32, 0), $fingerprint['data']);
        $this->assertSame($fingerprint, $Fingerprinter->calculate('smi', 'CCO'));
        $this->assertNotSame($fingerprint, $Fingerprinter->calculate('smi', 'CCN'));
    }

    public function testInvalidFormat(): void
    {
        $Fingerprinter = new OpenBabelFingerprinter();

        $this->expectException(ImproperActionException::class);
        $Fingerprinter->calculate('mol', 'CCO');
    }

    public function testEmptySmiles(): void
    {
        $Fingerprinter = new OpenBabelFingerprinter();

        $this->expectException(ImproperActionException::class);
        $Fingerprinter->calculate('smi', '');
    }

    public function testMultilineSmiles(): void
    {
        $Fingerprinter = new OpenBabelFingerprinter();

        $this->expectException(ImproperActionException::class);
        $Fingerprinter->calculate('smi', "CCO\nCCN");
    }
}
