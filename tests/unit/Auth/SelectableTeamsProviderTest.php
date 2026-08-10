<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Services\UsersHelper;
use PHPUnit\Framework\TestCase;

use function count;

final class SelectableTeamsProviderTest extends TestCase
{
    public function testGetForUserMapsSelectableTeams(): void
    {
        $rawTeams = new UsersHelper(1)->getSelectableTeams();
        $teams = new SelectableTeamsProvider()->getForUser(1)->all();

        self::assertNotEmpty($rawTeams);
        self::assertCount(count($rawTeams), $teams);

        foreach ($teams as $index => $team) {
            self::assertSame((int) $rawTeams[$index]['id'], $team->id);
            self::assertSame($rawTeams[$index]['name'], $team->name);
            self::assertSame((bool) $rawTeams[$index]['is_owner'], $team->isOwner);
            self::assertSame((bool) $rawTeams[$index]['is_admin'], $team->isAdmin);
        }
    }
}
