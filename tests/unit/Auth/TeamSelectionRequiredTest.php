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

use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use PHPUnit\Framework\TestCase;

final class TeamSelectionRequiredTest extends TestCase
{
    public function testKeepsAuthenticationAndTeams(): void
    {
        $authentication = new Authentication(1, AuthMethod::Ldap);
        $teams = new SelectableTeams(array(
            new SelectableTeam(1, 'Alpha', false, true),
            new SelectableTeam(2, 'Bravo', false, false),
        ));
        $step = new TeamSelectionRequired($authentication, $teams);

        self::assertSame($authentication, $step->authentication);
        self::assertSame($teams, $step->teams);
    }
}
