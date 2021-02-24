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
use Elabftw\Interfaces\AuthInterface;

/**
 * Local auth service
 */
class LocalAuth implements AuthInterface
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var string $email */
    private $email = '';

    /** @var string $password */
    private $password = '';

    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    public function __construct(string $email, string $password)
    {
        $this->Db = Db::getConnection();
        $this->email = Filter::sanitize($email);
        $this->password = $password;
        $this->AuthResponse = new AuthResponse('local');
    }

    public function tryAuth(): AuthResponse
    {
        $passwordHash = hash('sha512', $this->getSalt() . $this->password);

        $sql = 'SELECT userid, mfa_secret FROM users WHERE email = :email AND password = :passwordHash
            AND validated = 1 AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->email);
        $req->bindParam(':passwordHash', $passwordHash);
        $this->Db->execute($req);

        if ($req->rowCount() !== 1) {
            throw new InvalidCredentialsException();
        }

        $res = $req->fetch();

        $this->AuthResponse->userid = (int) $res['userid'];
        $this->AuthResponse->mfaSecret = $res['mfa_secret'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }

    /**
     * Get the salt for the user so we can generate a correct hash
     *
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
}
