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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\HashInterface;
use Override;

use function fclose;
use function hash_final;
use function hash_init;
use function hash_update_stream;

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
    public function getSafeHash(): string
    {
        $hash = $this->getHash();
        if ($hash === null) {
            throw new ImproperActionException('Could not get hash');
        }
        return $hash;
    }

    #[Override]
    public function getAlgo(): string
    {
        return self::HASH_ALGORITHM;
    }

    /**
     * @return resource
     */
    abstract protected function getContent();

    abstract protected function getStringContent(): string;

    protected function compute(): ?string
    {
        $stream = $this->getContent();
        $context = hash_init($this->getAlgo());
        hash_update_stream($context, $stream);
        fclose($stream);
        return hash_final($context);
    }
}
