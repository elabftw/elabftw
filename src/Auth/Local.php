<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Services\Filter;
use Elabftw\Services\UsersHelper;
use PDO;
use SensitiveParameter;
use Override;

use function password_hash;
use function password_needs_rehash;
use function password_verify;

/**
 * Local auth service
 */
final class Local implements AuthInterface
{
    private Db $Db;

    private int $userid;

    public function __construct(
        private string $email,
        #[SensitiveParameter]
        private readonly string $password,
        private readonly bool $isDisplayed = true,
        private readonly bool $isOnlySysadminWhenHidden = false,
        private readonly bool $isOnlySysadmin = false,
        private readonly int $maxPasswordAgeDays = 0,
        private readonly int $maxLoginAttempts = 3,
    ) {
        if (empty($password)) {
            throw new QuantumException(_('Invalid email/password combination.'));
        }
        $this->Db = Db::getConnection();
        $this->email = Filter::sanitizeEmail($email);
        $this->userid = $this->getUseridFromEmail();
    }

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        $this->preventBruteForce();

        $sql = 'SELECT is_sysadmin, password_hash, validated, password_modified_at FROM users WHERE userid = :userid;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();

        // if local_login is disabled, only a sysadmin can login if local_login_hidden_only_sysadmin is set
        if (!$this->isDisplayed && $res['is_sysadmin'] === 0 && $this->isOnlySysadminWhenHidden) {
            throw new ImproperActionException(_('Only a Sysadmin account can use local authentication when it is hidden.'));
        }
        // there is also a setting that only allows sysadmins to login
        if ($this->isOnlySysadmin && $res['is_sysadmin'] === 0) {
            throw new ImproperActionException(_('Only a Sysadmin account can use local authentication.'));
        }

        // verify password
        if (password_verify($this->password, $res['password_hash']) !== true) {
            throw new InvalidCredentialsException($this->userid);
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

        $AuthResponse = new AuthResponse();
        // check if last password modification date was too long ago and require changing it if yes
        if ($this->maxPasswordAgeDays > 0) {
            $modifiedAt = new DateTimeImmutable($res['password_modified_at']);
            $now = new DateTimeImmutable();
            $diff = $now->diff($modifiedAt);
            $daysDifference = (int) $diff->format('%a');
            $AuthResponse->mustRenewPassword = $daysDifference > $this->maxPasswordAgeDays;
        }

        $AuthResponse->userid = $this->userid;
        // TODO maybe auth class shouldn't have the responsability of setting the teams, we can do that in the controller
        $UsersHelper = new UsersHelper($AuthResponse->userid);
        return $AuthResponse->setTeams($UsersHelper);
    }

    private function preventBruteForce(): void
    {
        $sql = 'SELECT COUNT(id) AS failed_attempts
            FROM authfail
            WHERE attempt_time >= NOW() - INTERVAL 1 MINUTE';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $res = $req->fetch();
        if ($res['failed_attempts'] > $this->maxLoginAttempts) {
            throw new ImproperActionException(_('Too many authentication tries in the last minute. Please try later.'));
        }
        // also make subsequent attempts slower
        if ($res['failed_attempts'] > 0) {
            sleep($res['failed_attempts']);
        }
    }

    private function getUseridFromEmail(): int
    {
        try {
            $Users = ExistingUser::fromEmail($this->email);
        } catch (ResourceNotFoundException) {
            // here we rethrow an quantum exception because we don't want to let the user know if the email exists or not
            throw new QuantumException(_('Invalid email/password combination.'));
        }
        return $Users->userData['userid'];
    }
}
