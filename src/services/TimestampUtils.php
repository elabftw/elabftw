<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @author David Müller
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use function is_readable;
use Symfony\Component\Process\Process;

/**
 * Timestamp utilities
 */
class TimestampUtils
{
    public bool $isReady = false;

    public function __construct(
        private array $tsConfig,
        // this can be set with fixture files in tests
        private string $dataPath = '',
        private string $tokenPath = '',
    ) {
        if (!empty($dataPath) && !empty($tokenPath)) {
            $this->isReady = true;
        }
    }

    public function setDataTokenPaths(string $dataPath, string $tokenPath): void
    {
        $this->dataPath = $dataPath;
        $this->tokenPath = $tokenPath;
        $this->isReady = true;
    }

    /**
     * Create a Timestamp Requestfile from a file
     *
    public function createRequestfile($filePath): void
    {
        $this->runProcess(array(
            'openssl',
            'ts',
            '-query',
            '-data',
            $this->dataPath,
            '-cert',
            '-' . $this->tsConfig['ts_hash'],
            '-no_nonce',
            '-out',
            $filePath,
        ));
    }
     */
    public function verify(): bool
    {
        if (!$this->isReady) {
            throw new ImproperActionException('Data not set for ts verification!');
        }

        if (!is_readable($this->tsConfig['ts_cert'])) {
            throw new ImproperActionException('Cannot read the certificate file!');
        }

        $this->runProcess(array(
            'openssl',
            'ts',
            '-verify',
            '-data',
            $this->dataPath,
            '-in',
            $this->tokenPath,
            '-CAfile',
            $this->tsConfig['ts_chain'],
            '-untrusted',
            $this->tsConfig['ts_cert'],
        ));
        // a ProcessFailedException will be thrown if it fails
        return true;
    }

    /**
     * Run a process
     *
     * @param array<string> $args arguments including the executable
     * @param string|null $cwd command working directory
     */
    private function runProcess(array $args, ?string $cwd = null): string
    {
        $Process = new Process($args, $cwd);
        $Process->mustRun();

        return $Process->getOutput();
    }
}
