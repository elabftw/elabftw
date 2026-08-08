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
    case Anonymous = 'anon';
    case Mfa = 'mfa';
    case SelectTeam = 'team';
    case JoinTeam = 'teamselection';
    case InitTeam = 'teaminit';
    case SamlStart = 'saml';
    case Local = 'local';
    case Ldap = 'ldap';
    case External = 'external';
    case Demo = 'demo';
    case PasswordRenewal = 'password_renewal';
    case SamlResponse = 'saml_response';
}
