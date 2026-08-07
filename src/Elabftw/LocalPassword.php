<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\HashInterface;
use Elabftw\Interfaces\PasswordInterface;
use Elabftw\Interfaces\ValidatorInterface;
use Override;

class LocalPassword implements PasswordInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly HashInterface $hasher,
    ) {}

    #[Override]
    public function toHash(): string
    {
        if ($this->validator->validate()) {
            return $this->hasher->getSafeHash();
        }
        throw new ImproperActionException('Could not hash password');
    }
}
