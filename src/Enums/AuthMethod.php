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

enum AuthMethod: int
{
    case Local = 10;
    case Saml = 20;
    case Ldap = 30;
    case External = 40;
    case Demo = 5;
    case Cookie = 60;
}
