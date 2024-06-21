<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\AuditEvent\UserLogin;
use Elabftw\Auth\CookieToken;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Config;
use Elabftw\Models\Notifications\NewVersionInstalled;
use PDO;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function time;

/**
 * Methods to login the user (once the authentication is done)
 */
class LoginHelper
{
    private Db $Db;

    public function __construct(private AuthResponse $AuthResponse, private SessionInterface $Session)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Login means having some anon / auth in session + team + userid
     * and set the cookie "token" if it was requested
     */
    public function login(bool $setCookie): void
    {
        $this->checkAccountValidity();
        $this->populateSession();
        if ($setCookie) {
            $this->setToken();
        }
        // if we run a version newer than the last time the user logged in, create a notification
        // but only if it's a minor version
        if ((App::INSTALLED_VERSION_INT - $this->getLastSeenVersion() >= 100) && $this->AuthResponse->isAnonymous === false) {
            $Notifications = new NewVersionInstalled();
            $Notifications->create($this->AuthResponse->userid);
        }
        $this->updateLast();
        $this->setDeviceToken();
        // only update this value if it is set, won't be set for cookie login for instance
        if ($this->Session->has('auth_service')) {
            $this->updateAuthService();
        }
        AuditLogs::create(new UserLogin($this->AuthResponse->userid, $this->AuthResponse->userid));
    }

    public function getExpires(): int
    {
        return time() + 60 * ((int) Config::getConfig()->configArr['cookie_validity_time']);
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Also set a token_team cookie for the team
     */
    private function setToken(): void
    {
        $CookieToken = CookieToken::fromScratch();
        $CookieToken->saveToken($this->AuthResponse->userid);

        $cookieOptions = array(
            'expires' => $this->getExpires(),
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie('token', $CookieToken->getToken(), $cookieOptions);
        setcookie('token_team', (string) $this->AuthResponse->selectedTeam, $cookieOptions);
    }

    private function getLastSeenVersion(): int
    {
        $sql = 'SELECT last_seen_version FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Update the authentication service used
     */
    private function updateAuthService(): void
    {
        $sql = 'UPDATE users SET auth_service = :auth_service WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $req->bindValue(':auth_service', $this->Session->get('auth_service'), PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Update last login time of user and last seen version
     */
    private function updateLast(): void
    {
        $sql = 'UPDATE users SET last_login = NOW(), last_seen_version = :version WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':version', App::INSTALLED_VERSION_INT, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Verify account validity date
     */
    private function checkAccountValidity(): void
    {
        if ($this->AuthResponse->isAnonymous) {
            return;
        }
        $sql = "SELECT IFNULL(valid_until, '3000-01-01') > NOW() FROM users WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = (bool) $req->fetchColumn();
        if ($res === false) {
            throw new ImproperActionException(_('Your account has expired. Contact your team Admin to extend its validity.'));
        }
    }

    private function setDeviceToken(): void
    {
        // set device token as a cookie
        $cookieOptions = array(
            'expires' => time() + 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );

        setcookie('devicetoken', DeviceToken::getToken($this->AuthResponse->userid), $cookieOptions);
    }

    /**
     * Store userid in session
     */
    private function populateSession(): void
    {
        // Main switch to know if we are logged in
        $this->Session->set('is_auth', 1);

        // ANY LOGIN needs to have a team
        $this->Session->set('team', $this->AuthResponse->selectedTeam);

        // ANON will get userid 0 here
        $this->Session->set('userid', $this->AuthResponse->userid);

        // ANON LOGIN
        if ($this->AuthResponse->isAnonymous) {
            $this->Session->set('is_anon', 1);
        }
    }
}
