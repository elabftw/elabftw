<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Interfaces\FingerprinterInterface;
use Override;

/**
 * Use when fingerprinter service is disabled
 */
final class NullFingerprinter implements FingerprinterInterface
{
    #[Override]
    public function calculate(string $fmt, string $data): array
    {
        return array('data' => array(0));
    }
}
