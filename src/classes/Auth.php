<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide methods to login a user
 */
class Auth
{
    /** @var SessionInterface $Session the current session */
    public $Session;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Request $Request current request */
    private $Request;

    /** @var array $userData All the user data for a user */
    private $userData = array();

    /**
     * Constructor
     *
     * @param Request $request
     * @param SessionInterface $session
     */
    public function __construct(Request $request, SessionInterface $session)
    {
        $this->Db = Db::getConnection();
        $this->Request = $request;
        $this->Session = $session;
    }

    /**
     * Test email and password in the database
     *
     * @param string $email
     * @param string $password
     * @return int userid
     */
    public function checkCredentials(string $email, string $password): int
    {
        $passwordHash = hash('sha512', $this->getSalt($email) . $password);

        $sql = 'SELECT userid FROM users WHERE email = :email AND password = :passwordHash
            AND validated = 1 AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $req->bindParam(':passwordHash', $passwordHash);
        $this->Db->execute($req);

        if ($req->rowCount() !== 1) {
            throw new InvalidCredentialsException();
        }

        return (int) $req->fetchColumn();
    }

    public function loginInTeam(int $userid, int $team, string $setCookie = 'on'): void
    {
        $this->populateUserDataFromUserid($userid);
        $this->populateSession($team);
        if ($setCookie === 'on') {
            $this->setToken($team);
        }
    }

    /**
     * Login with email and password
     *
     * @param string $setCookie will be here if the user ticked the remember me checkbox
     * @return mixed Return true if user provided correct credentials or an array with the userid
     * and the teams where login is possible for display on the team selection page
     */
    public function login(int $userid, string $setCookie = 'on')
    {
        $UsersHelper = new UsersHelper();
        $teams = $UsersHelper->getTeamsFromUserid($userid);
        if (\count($teams) > 1) {
            return array($userid, $teams);
        }
        $this->loginInTeam($userid, (int) $teams[0]['id']);

        return true;
    }

    /**
     * Login anonymously in a team
     *
     * @param int $team
     * @return void
     */
    public function loginAsAnon(int $team): void
    {
        $this->Session->set('anon', 1);
        $this->Session->set('team', $team);

        $this->Session->set('is_admin', 0);
        $this->Session->set('is_sysadmin', 0);
    }

    /**
     * Check if we need to bother with authentication of current user
     *
     * @return bool True if we are authentified (or if we don't need to be)
     */
    public function needAuth(): bool
    {
        // pages where you don't need to be logged in
        // only the script name, not the path because we use basename() on it
        $nologinArr = array(
            'change-pass.php',
            'index.php',
            'login.php',
            'LoginController.php',
            'metadata.php',
            'register.php',
            'RegisterController.php',
            'ResetPasswordController.php',
        );

        return !\in_array(\basename($this->Request->getScriptName()), $nologinArr, true);
    }

    /**
     * Try to authenticate with session and cookie
     *     ____          _
     *    / ___|___ _ __| |__   ___ _ __ _   _ ___
     *   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|
     *   | |___  __/ |  | |_) |  __/ |  | |_| \__ \
     *    \____\___|_|  |_.__/ \___|_|   \__,_|___/
     *
     * @return bool true if we are authenticated
     */
    public function tryAuth(): bool
    {
        if ($this->Session->has('anon')) {
            return true;
        }
        // if we are already logged in with the session, skip everything
        if ($this->Session->has('auth')) {
            return true;
        }

        // now try to login with the cookie
        if ($this->loginWithCookie()) {
            // successful login thanks to our cookie friend
            $team = (int) $this->Request->cookies->filter('token_team', null, FILTER_SANITIZE_STRING);
            $this->populateSession($team);
            return true;
        }

        return false;
    }

    public function getUseridFromEmail(string $email): int
    {
        $sql = 'SELECT userid FROM users WHERE email = :email AND archived = 0 AND validated = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $this->Db->execute($req);
        if ($req->rowCount() !== 1) {
            return 0;
        }
        return (int) $req->fetchColumn();
    }

    /**
     * Get the salt for the user so we can generate a correct hash
     *
     * @param string $email
     * @return string
     */
    private function getSalt(string $email): string
    {
        $sql = 'SELECT salt FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            throw new ImproperActionException(_("Login failed. Either you mistyped your password or your account isn't activated yet."));
        }
        return (string) $res;
    }

    /**
     * Login with the cookie
     *
     * @return bool true if token in cookie is found in database
     */
    private function loginWithCookie(): bool
    {
        // If user has a cookie; check cookie is valid
        // the token is a sha256 sum: 64 char
        if (!$this->Request->cookies->has('token') || \mb_strlen($this->Request->cookies->get('token')) !== 64) {
            return false;
        }
        $token = $this->Request->cookies->filter('token', null, FILTER_SANITIZE_STRING);


        // Now compare current cookie with the token from SQL
        $sql = 'SELECT userid FROM users WHERE token = :token LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $token);
        $this->Db->execute($req);
        if ($req->rowCount() !== 1) {
            return false;
        }
        $userid = (int) $req->fetchColumn();
        // make sure user is in team
        $team = (int) $this->Request->cookies->filter('token_team', null, FILTER_SANITIZE_STRING);
        $Teams = new Teams(new Users($userid));
        if (!$Teams->isUserInTeam($userid, $team)) {
            return false;
        }
        return $this->populateUserDataFromUserid($userid);
    }

    /**
     * Update last login time of user
     *
     * @return void
     */
    private function updateLastLogin(): void
    {
        $sql = 'UPDATE users SET last_login = :last_login WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':last_login', \date('Y-m-d H:i:s'));
        $req->bindParam(':userid', $this->userData['userid']);
        $this->Db->execute($req);
    }

    /**
     * Populate userData from userid
     *
     * @param int $userid
     * @return bool
     */
    private function populateUserDataFromUserid(int $userid): bool
    {
        $sql = 'SELECT * FROM users WHERE userid = :userid AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        if ($req->rowCount() === 1) {
            // populate the userData
            $this->userData = $req->fetch();
            $this->updateLastLogin();
            return true;
        }
        return false;
    }

    /**
     * Populate userData from email
     *
     * @param string $email
     * @return bool
     */
    private function populateUserDataFromEmail(string $email): bool
    {
        $sql = 'SELECT * FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $this->Db->execute($req);
        if ($req->rowCount() === 1) {
            // populate the userData
            $this->userData = $req->fetch();
            $this->updateLastLogin();
            return true;
        }
        return false;
    }

    /**
     * Store userid and permissions in session
     *
     * @return void
     */
    private function populateSession(int $team): void
    {
        $this->Session->set('auth', 1);
        $this->Session->set('userid', $this->userData['userid']);
        $this->Session->set('team', $team);

        // load permissions
        $sql = 'SELECT * FROM `groups` WHERE id = :id LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->userData['usergroup'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $group = $req->fetch(PDO::FETCH_ASSOC);

        $this->Session->set('is_admin', $group['is_admin']);
        $this->Session->set('is_sysadmin', $group['is_sysadmin']);
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Works only in HTTPS, valable for 1 month.
     * 1 month = 60*60*24*30 =  2592000
     *
     * @param int $team
     * @return void
     */
    private function setToken(int $team): void
    {
        $token = \hash('sha256', \bin2hex(\random_bytes(16)));

        // create cookie for login
        $cookieOptions = array(
            'expires' => time() + 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        \setcookie('token', $token, $cookieOptions);
        \setcookie('token_team', (string) $team, $cookieOptions);

        // Update the token in SQL
        $sql = 'UPDATE users SET token = :token WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $token);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
