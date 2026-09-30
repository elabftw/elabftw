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
use RuntimeException;

use function mb_strlen;
use function hash;

class StringHash extends AbstractHash
{
    // length of input above which we don't process it
    protected const int THRESHOLD = 268_435_456;

    public function __construct(protected readonly string $input) {}

    #[Override]
    /**
     * @return resource
     */
    protected function getContent()
    {
        throw new RuntimeException('getContent() is not implemented on StringHash');
    }

    #[Override]
    protected function getStringContent(): string
    {
        return $this->input;
    }

    #[Override]
    protected function canCompute(): bool
    {
        return mb_strlen($this->input) < self::THRESHOLD;
    }

    #[Override]
    protected function compute(): ?string
    {
        if ($this->canCompute()) {
            return hash(self::HASH_ALGORITHM, $this->getStringContent());
        }
        return null;
    }
}
