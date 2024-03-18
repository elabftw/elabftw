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
 * A user that exists in the db, so we have a userid but not necessarily a team, and they might not be validated
 */
class ExistingUser extends Users
{
    public static function fromEmail(string $email): Users
    {
        return self::search('email', $email);
    }

    public static function fromOrgid(string $orgid): Users
    {
        return self::search('orgid', $orgid);
    }

    public static function fromScratch(
        string $email,
        array $teams,
        string $firstname,
        string $lastname,
        ?Usergroup $usergroup = null,
        bool $automaticValidationEnabled = false,
        bool $alertAdmin = true,
    ): Users {
        $Users = new self();
        $userid = $Users->createOne($email, $teams, $firstname, $lastname, '', $usergroup, $automaticValidationEnabled, $alertAdmin);
        $fresh = new self($userid);
        // we need to report the needValidation flag into the new object
        $fresh->needValidation = $Users->needValidation;
        return $fresh;
    }
}
