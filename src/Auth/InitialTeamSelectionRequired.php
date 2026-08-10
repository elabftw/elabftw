<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

final readonly class InitialTeamSelectionRequired
{
    public function __construct(
        public string $email,
        public string $firstname,
        public string $lastname,
        public ?string $orgid = null,
        public ?string $orcid = null,
    ) {}

    public function toArray(): array
    {
        return array(
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'orgid' => $this->orgid,
            'orcid' => $this->orcid,
        );
    }
}
