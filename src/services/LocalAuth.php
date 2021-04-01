<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;
use function password_hash;
use function password_needs_rehash;
use function password_verify;
use PDO;

/**
 * Local auth service
 */
class LocalAuth implements AuthInterface
{
    private Db $Db;

    private string $email = '';

    private int $userid;

    private string $password = '';

    private AuthResponse $AuthResponse;

    public function __construct(string $email, string $password)
    {
        $this->Db = Db::getConnection();
        $this->email = Filter::sanitize($email);
        $this->userid = $this->getUseridFromEmail();
        $this->password = $password;
        $this->AuthResponse = new AuthResponse('local');
    }

    public function tryAuth(): AuthResponse
    {
        if ($this->needUpgrade()) {
            // authenticate with old password mechanism first
            $this->authWithSha();
            // and then upgrade to new algorithm
            $this->upgrade();
        }
        $this->authWithModernAlgo();
        return $this->AuthResponse;
    }

    /**
     * Get the salt for the user so we can generate a correct hash
     */
    private function getSalt(): string
    {
        $sql = 'SELECT salt FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->email);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            throw new InvalidCredentialsException();
        }
        return (string) $res;
    }

    private function getUseridFromEmail(): int
    {
        $Users = new Users();
        try {
            $Users->populateFromEmail($this->email);
            // if the email is not found, transform the exception in the general invalidcredentials error
        } catch (ResourceNotFoundException $e) {
            throw new InvalidCredentialsException();
        }
        return (int) $Users->userData['userid'];
    }

    /**
     * A user account needs upgrade if they have nothing in password_hash column
     */
    private function needUpgrade(): bool
    {
        $sql = 'SELECT password_hash FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        return $res === null;
    }

    /**
     * Upgrade means we create a new password hash with modern algorithm
     */
    private function upgrade(): void
    {
        $passwordHash = password_hash($this->password, PASSWORD_DEFAULT);
        $sql = 'UPDATE users SET password_hash = :password_hash WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        // clear old columns, we don't need to keep the salted sha around anymore
        $sql = 'UPDATE users SET `password` = NULL, `salt` = NULL WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Old mechanism to authenticate users
     */
    private function authWithSha(): void
    {
        $passwordHash = hash('sha512', $this->getSalt() . $this->password);

        $sql = 'SELECT userid FROM users WHERE email = :email AND password = :passwordHash
            AND validated = 1 AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->email);
        $req->bindParam(':passwordHash', $passwordHash);
        $this->Db->execute($req);

        if ($req->rowCount() !== 1) {
            throw new InvalidCredentialsException();
        }
    }

    private function authWithModernAlgo(): void
    {
        $sql = 'SELECT password_hash, mfa_secret FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        // verify password
        if (password_verify($this->password, $res['password_hash']) !== true) {
            throw new InvalidCredentialsException();
        }
        // check if it needs rehash (new algo)
        if (password_needs_rehash($res['password_hash'], PASSWORD_DEFAULT)) {
            $passwordHash = password_hash($this->password, PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET password_hash = :password_hash WHERE userid = :userid';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':password_hash', $passwordHash);
            $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
            $this->Db->execute($req);
        }

        $this->AuthResponse->userid = $this->userid;
        $this->AuthResponse->mfaSecret = $res['mfa_secret'];
        $this->AuthResponse->setTeams();
    }
}
