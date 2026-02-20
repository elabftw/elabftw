<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

final readonly class AccessPermissions
{
    public function __construct(
        public bool  $read,
        public bool  $write,
        public ?bool $book = null, // nullable for experiments
    ) {}
}
