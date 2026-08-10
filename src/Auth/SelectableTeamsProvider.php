<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Services\UsersHelper;

use function array_map;

final readonly class SelectableTeamsProvider
{
    public function getForUser(int $userid): SelectableTeams
    {
        $teams = new UsersHelper($userid)->getSelectableTeams();

        return new SelectableTeams(
            array_map(
                static fn(array $team): SelectableTeam => new SelectableTeam(
                    id: (int) $team['id'],
                    name: $team['name'],
                    isOwner: (bool) $team['is_owner'],
                    isAdmin: (bool) $team['is_admin'],
                ),
                $teams,
            ),
        );
    }
}
