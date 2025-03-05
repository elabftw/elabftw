<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Exceptions\IllegalActionException;
use Override;

/**
 * Create a report of usage for users in a team
 * We check that the user is Admin and use the current team to generate the list of users
 */
final class MakeTeamReport extends MakeReport
{
    #[Override]
    protected function canReadOrExplode(): void
    {
        if (!$this->requester->isAdmin) {
            throw new IllegalActionException('Non Admin user tried to generate report.');
        }
    }

    #[Override]
    protected function readUsers(): array
    {
        return $this->requester->readFromQuery('', includeArchived: true, teamId: $this->requester->userData['team']);
    }
}
