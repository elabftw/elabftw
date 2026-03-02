<?php

/**
 * @author Nicolas CARPi <Deltablot>
 * @author Mouss <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\AccessType;

final readonly class AccessPermissions
{
    public function __construct(
        public bool $read = false,
        public bool $write = false,
        public bool $book = false,
    ) {}

    public function fromCan(AccessType $can): bool
    {
        return match ($can) {
            AccessType::Read => $this->read,
            AccessType::Write => $this->write,
            AccessType::Book => $this->book,
        };
    }
}
