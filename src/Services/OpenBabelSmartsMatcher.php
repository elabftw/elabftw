<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\SmartsMatcherInterface;
use Override;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function _;
use function array_keys;
use function array_values;
use function bin2hex;
use function ctype_digit;
use function end;
use function in_array;
use function mkdir;
use function preg_match;
use function preg_split;
use function random_bytes;
use function rmdir;
use function str_contains;
use function sys_get_temp_dir;
use function trim;

/**
 * Substructure search with a SMARTS pattern, with the -s filter of Open Babel:
 * all the structures go through a single obabel process, which writes back the ones that match.
 */
final class OpenBabelSmartsMatcher implements SmartsMatcherInterface
{
    // what obabel writes to stderr when the pattern of -s is not valid SMARTS
    private const string INVALID_SMARTS = 'cannot be interpreted as either valid SMARTS';

    // always the first molecule: obabel reads the pattern with the first molecule, so an invalid one is reported even without structures
    private const string PROBE = 'C';

    public function __construct(private int $timeout = 30) {}

    #[Override]
    public function matches(string $smarts, array $smiles): array
    {
        self::validate($smarts);
        $keys = array_keys($smiles);
        // the title of each molecule is its index, obabel writes it after the SMILES of the molecules that match
        $input = self::PROBE . " probe\n";
        foreach (array_values($smiles) as $index => $structure) {
            $input .= $structure . ' ' . $index . "\n";
        }

        // -s reads its parameter as a file of pattern molecules when such a file exists: run it where there is none
        $cwd = sys_get_temp_dir() . '/elabftw-smarts-' . bin2hex(random_bytes(8));
        if (!mkdir($cwd, 0o700)) {
            throw new ImproperActionException('Could not prepare the SMARTS search.');
        }
        try {
            $process = new Process(array('/usr/local/bin/obabel', '-ismi', '-osmi', '-s', $smarts), $cwd);
            $process->setInput($input);
            $process->setTimeout($this->timeout);
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new ImproperActionException(_('The SMARTS search took too long: add other criteria to narrow it.'));
        } finally {
            rmdir($cwd);
        }
        if (str_contains($process->getErrorOutput(), self::INVALID_SMARTS)) {
            throw new ImproperActionException(_('Invalid SMARTS pattern.'));
        }
        if (!$process->isSuccessful()) {
            throw new ImproperActionException('Could not run the SMARTS search.');
        }

        $matches = array();
        foreach (preg_split('/\R/', trim($process->getOutput())) ?: array() as $line) {
            $title = preg_split('/\s+/', trim($line));
            $index = $title === false ? '' : (string) end($title);
            // each molecule is written at most once, and the probe has no numeric title
            if (ctype_digit($index) && isset($keys[(int) $index])) {
                $matches[] = $keys[(int) $index];
            }
        }
        return $matches;
    }

    /**
     * obabel splits the parameter of -s on whitespace, inverts the match with a leading ~, and reads it as a path when it can.
     * No SMARTS starts with a path or option character, nor contains whitespace.
     */
    private static function validate(string $smarts): void
    {
        if ($smarts === '' || preg_match('/[\s\x00-\x1F]/', $smarts) === 1 || in_array($smarts[0], array('/', '.', '~', '-'), true)) {
            throw new ImproperActionException(_('Invalid SMARTS pattern.'));
        }
    }
}
