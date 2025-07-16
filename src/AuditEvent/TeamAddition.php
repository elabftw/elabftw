<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Override;

final class TeamAddition extends AbstractUsers2TeamsModifiedEvent
{
    public function __construct(private int $teamid, private int $isAdmin, int $requester, int $userid)
    {
        parent::__construct($requester, $userid);
    }

    #[Override]
    public function getBody(): string
    {
        $level = $this->isAdmin === 1 ? 'Admin' : 'User';
        return sprintf('User was associated with team %d and permission level %s', $this->teamid, $level);
    }
}
