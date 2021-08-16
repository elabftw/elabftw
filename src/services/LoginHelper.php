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

use function bin2hex;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Models\Users;
use function hash;
use PDO;
use function random_bytes;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
        $this->populateSession();
        if ($setCookie) {
            $this->setToken();
        }
        $this->updateLastLogin();
        $this->setDeviceToken();
    }

    /**
     * Update last login time of user
     */
    private function updateLastLogin(): void
    {
        $sql = 'UPDATE users SET last_login = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
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
            'samesite' => 'Strict',
        );

        setcookie('devicetoken', DeviceToken::getToken($this->AuthResponse->userid), $cookieOptions);
    }

    /**
     * Store userid and permissions in session
     */
    private function populateSession(): void
    {
        // Main switch to know if we are logged in
        $this->Session->set('is_auth', 1);

        // ANY LOGIN needs to have a team
        $this->Session->set('team', $this->AuthResponse->selectedTeam);

        // ANON will get userid 0 here
        $this->Session->set('userid', $this->AuthResponse->userid);

        // store the auth method so the logout page will know what to do
        // TODO this doesn't work if mfa or team second step
        $this->Session->set('is_auth_by', $this->AuthResponse->isAuthBy);

        // ANON LOGIN
        if ($this->AuthResponse->isAnonymous) {
            $this->Session->set('is_anon', 1);
            $this->Session->set('is_admin', 0);
            $this->Session->set('is_sysadmin', 0);
            return;
        }

        // NORMAL LOGIN
        // load the permissions
        $Users = new Users($this->AuthResponse->userid);
        $this->Session->set('is_admin', $Users->userData['is_admin']);
        $this->Session->set('is_sysadmin', $Users->userData['is_sysadmin']);
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Works only in HTTPS, valable for 1 month.
     * 1 month = 60*60*24*30 =  2592000
     */
    private function setToken(): void
    {
        $token = hash('sha256', bin2hex(random_bytes(16)));

        // create cookie for login
        $cookieOptions = array(
            'expires' => time() + 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        );
        setcookie('token', $token, $cookieOptions);
        setcookie('token_team', (string) $this->AuthResponse->selectedTeam, $cookieOptions);

        // Update the token in SQL
        $sql = 'UPDATE users SET token = :token WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $token);
        $req->bindParam(':userid', $this->AuthResponse->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
