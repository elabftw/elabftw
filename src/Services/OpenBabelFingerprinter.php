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
use Elabftw\Interfaces\FingerprinterInterface;
use Symfony\Component\Process\Process;

use function trim;

final class OpenBabelFingerprinter implements FingerprinterInterface
{
    public function calculate(string $fmt, string $data): array
    {
        if ($fmt !== 'smi') {
            throw new ImproperActionException('Only SMILES input is supported.');
        }

        if ($data === '' || str_contains($data, "\n") || str_contains($data, "\r")) {
            throw new ImproperActionException('Invalid SMILES.');
        }

        $process = new Process(array(
            '/usr/local/bin/obabel',
            '-ismi',
            '-ofpt',
            '-xo',
            '-xfFP2',
        ));

        $process->setInput($data . "\n");
        $process->setTimeout(5);
        $process->mustRun();

        $words = preg_split(
            '/\s+/',
            trim($process->getOutput()),
            flags: PREG_SPLIT_NO_EMPTY,
        );

        if (
            $words === false
            || count($words) !== 32
            || array_any(
                $words,
                static fn(string $word): bool => preg_match('/^[0-9a-fA-F]{8}$/', $word) !== 1,
            )
        ) {
            throw new ImproperActionException('Invalid fingerprint returned by Open Babel.');
        }

        return array(
            'data' => array_map(
                static fn(string $word): int => intval($word, 16),
                array_reverse($words),
            ),
        );
    }
}
