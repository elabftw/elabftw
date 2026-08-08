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

use LogicException;

use function count;

final readonly class SelectableTeams
{
    /**
     * @param array<int, SelectableTeam> $teams
     */
    public function __construct(
        private array $teams,
    ) {}

    public function count(): int
    {
        return count($this->teams);
    }

    public function first(): SelectableTeam
    {
        if (!isset($this->teams[0])) {
            throw new LogicException('No selectable team available.');
        }

        return $this->teams[0];
    }

    public function contains(int $teamId): bool
    {
        foreach ($this->teams as $team) {
            if ($team->id === $teamId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, SelectableTeam>
     */
    public function all(): array
    {
        return $this->teams;
    }
}
