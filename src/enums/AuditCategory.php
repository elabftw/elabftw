<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum AuditCategory: int
{
    case Login = 10;
    case Logout = 11;
    case AccountCreated = 20;
    case AccountValidated = 21;
    case AccountArchived = 22;
    case AccountDeleted = 23;
    case AccountModified = 24;
    case PasswordChanged = 30;
    case PasswordResetRequested = 31;
    case Users2TeamsModified = 40;
    case ApiKeyCreated = 50;
    case ApiKeyDeleted = 51;
    case ConfigModified = 60;
    case Export = 70;
    case Import = 80;
}
