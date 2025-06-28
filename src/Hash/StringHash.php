<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Hash;

use Override;

class StringHash extends AbstractHash
{
    // length of input above which we don't process it
    protected const int THRESHOLD = 268435456;

    public function __construct(protected readonly string $input) {}

    #[Override]
    protected function getContent(): string
    {
        return $this->input;
    }

    #[Override]
    protected function canCompute(): bool
    {
        return mb_strlen($this->input) < self::THRESHOLD;
    }
}
