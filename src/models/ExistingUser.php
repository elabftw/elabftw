<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ResourceNotFoundException;

/**
 * A user that exists in the db, so we have a userid but not necessarily a team
 */
class ExistingUser extends Users
{
    public static function fromEmail(string $email): Users
    {
        $Db = Db::getConnection();
        $sql = 'SELECT userid FROM users
            WHERE email = :email AND archived = 0 AND validated = 1 LIMIT 1';
        $req = $Db->prepare($sql);
        $req->bindParam(':email', $email);
        $Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false) {
            throw new ResourceNotFoundException();
        }
        return new self((int) $res);
    }

    public static function fromScratch(
        string $email,
        array $teams,
        string $firstname,
        string $lastname,
        ?int $usergroup = null,
        bool $forceValidation = false,
        bool $alertAdmin = true,
    ): Users {
        $Users = new self();
        $userid = $Users->create($email, $teams, $firstname, $lastname, '', $usergroup, $forceValidation, $alertAdmin);
        $Users->populate($userid);
        return $Users;
    }
}
