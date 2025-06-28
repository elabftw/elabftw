<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\HashInterface;
use Override;

use function hash;

class Hash implements HashInterface
{
    protected const string HASH_ALGORITHM = 'sha256';

    // length of input above which we don't process it
    protected const int THRESHOLD = 4200000000;

    protected ?string $hash = null;

    public function __construct(protected readonly string $input) {}

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

    // todo add getContent so compute can be the same in filehash
    protected function compute(): ?string
    {
        if ($this->canCompute()) {
            return hash(self::HASH_ALGORITHM, $this->input);
        }
        return null;
    }

    protected function canCompute(): bool
    {
        return mb_strlen($this->input) < self::THRESHOLD;
    }
}
