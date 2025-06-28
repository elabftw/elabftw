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

use Elabftw\Interfaces\HashInterface;
use Override;

use function hash;

abstract class AbstractHash implements HashInterface
{
    protected const string HASH_ALGORITHM = 'sha256';

    protected ?string $hash = null;

    #[Override]
    public function getHash(): ?string
    {
        if ($this->hash) {
            return $this->hash;
        }
        // we store it in memory because it's an expensive operation
        $this->hash = $this->compute();
        return $this->hash;
    }

    #[Override]
    public function getAlgo(): ?string
    {
        return self::HASH_ALGORITHM;
    }

    abstract protected function getContent(): string;

    protected function compute(): ?string
    {
        if ($this->canCompute()) {
            return hash(self::HASH_ALGORITHM, $this->getContent());
        }
        return null;
    }

    abstract protected function canCompute(): bool;
}
