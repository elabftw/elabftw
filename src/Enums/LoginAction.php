<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum LoginAction: string
{
    case Authenticate = 'authenticate';
    case Anonymous = 'anonymous';
    case Mfa = 'mfa';
    case SelectTeam = 'select_team';
    case JoinTeam = 'join_team';
    case InitTeam = 'init_team';
    case SamlResponse = 'saml_response';
    case SamlStart = 'saml_start';
}
