<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Symfony\Component\Process\Process;

/**
 * For executing local bin
 */
trait ProcessTrait
{
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
