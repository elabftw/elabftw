<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Usergroup;

/**
 * A user that exists in the db, so we have a userid but not necessarily a team
 */
class ValidatedUser extends ExistingUser
{
    public static function fromEmail(string $email): Users
    {
        return self::search('email', $email, true);
    }

    public static function fromOrgid(string $orgid): Users
    {
        return self::search('orgid', $orgid, true);
    }

    public static function fromExternal(string $email, array $teams, string $firstname, string $lastname): Users
    {
        return parent::fromScratch($email, $teams, $firstname, $lastname, null, true);
    }

    public static function fromAdmin(string $email, array $teams, string $firstname, string $lastname, Usergroup $usergroup): Users
    {
        return parent::fromScratch($email, $teams, $firstname, $lastname, $usergroup, true, false);
    }
}
