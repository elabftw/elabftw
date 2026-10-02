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

use function hash;

class StringHash extends AbstractHash
{
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
    protected function compute(): ?string
    {
        return hash(self::HASH_ALGORITHM, $this->getStringContent());
    }
}
