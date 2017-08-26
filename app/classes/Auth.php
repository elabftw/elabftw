<?php
/**
 * \Elabftw\Elabftw\Auth
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide methods to login a user
 */
class Auth
{
    /** the minimum password length */
    const MIN_PASSWORD_LENGTH = 8;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Request $Request current request with Session */
    private $Request;

    /** @var array $userData All the user data for a user */
    private $userData;

    /** @var string $token Token that will be in the cookie + db */
    private $token;

    /**
     * Just give me the Db object and I'm good to go
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->Db = Db::getConnection();
        $this->Request = $request;
    }

    /**
     * Get the salt for the user so we can generate a correct hash
     *
     * @param string $email
     * @return string
     */
    private function getSalt($email)
    {
        $sql = "SELECT salt FROM users WHERE email = :email";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $req->execute();
        return $req->fetchColumn();
    }

    /**
     * Test email and password in the database
     *
     * @param string $email
     * @param string $password
     * @return bool True if the login + password are good
     */
    public function checkCredentials($email, $password)
    {
        $passwordHash = hash('sha512', $this->getSalt($email) . $password);

        $sql = "SELECT * FROM users WHERE email = :email AND password = :passwordHash AND validated = 1";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        $req->bindParam(':passwordHash', $passwordHash);
        //Check whether the query was successful or not
        if ($req->execute() && $req->rowCount() === 1) {
            // populate the userData
            $this->userData = $req->fetch();
            return true;
        }
        return false;
    }

    /**
     * Check the number of character of a password
     *
     * @param string $password The password to check
     * @return bool true if the length is enough
     */
    public function checkPasswordLength($password)
    {
        return strlen($password) >= self::MIN_PASSWORD_LENGTH;
    }

    /**
     * Store userid and permissions in session
     *
     * @param string|null $email
     * @return bool
     */
    private function populateSession($email = null)
    {
        if ($email !== null) {
            $sql = "SELECT * FROM users WHERE email = :email";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':email', $email);
            //Check whether the query was successful or not
            if ($req->execute() && $req->rowCount() === 1) {
                // populate the userData
                $this->userData = $req->fetch();
            } else {
                return false;
            }
        }

        $this->Request->getSession()->migrate(true);
        $this->Request->getSession()->set('auth', 1);
        $this->Request->getSession()->set('userid', $this->userData['userid']);

        // load permissions
        $perm_sql = "SELECT * FROM groups WHERE group_id = :group_id LIMIT 1";
        $perm_req = $this->Db->prepare($perm_sql);
        $perm_req->bindParam(':group_id', $this->userData['usergroup']);
        $perm_req->execute();
        $group = $perm_req->fetch(PDO::FETCH_ASSOC);

        $this->Request->getSession()->set('is_admin', $group['is_admin']);
        $this->Request->getSession()->set('is_sysadmin', $group['is_sysadmin']);
        // create a token
        $this->token = md5(uniqid(rand(), true));

        return true;
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Works only in HTTPS, valable for 1 month.
     * 1 month = 60*60*24*30 =  2592000
     *
     * @return bool
     */
    private function setToken()
    {
        setcookie('token', $this->token, time() + 2592000, '/', null, true, true);
        // Update the token in SQL
        $sql = "UPDATE users SET token = :token WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $this->token);
        $req->bindParam(':userid', $this->userData['userid']);

        return $req->execute();
    }

    /**
     * Login with email and password
     *
     * @param string $email
     * @param string $password
     * @param string $setCookie will be here if the user ticked the remember me checkbox
     * @return bool Return true if user provided correct credentials
     */
    public function login($email, $password, $setCookie = 'on')
    {
        if ($this->checkCredentials($email, $password)) {
            $this->populateSession();
            if ($setCookie === 'on') {
                return $this->setToken();
            }
            return true;
        }
        return false;
    }

    /**
     * Login with the cookie
     *
     * @return bool true if token in cookie is found in database
     */
    private function loginWithCookie()
    {
        // If user has a cookie; check cookie is valid
        // the token is a md5 sum: 32 char
        if (!$this->Request->cookies->has('token') || strlen($this->Request->cookies->get('token')) != 32) {
            return false;
        }
        $token = $this->Request->cookies->filter('token', null, FILTER_SANITIZE_STRING);
        // Now compare current cookie with the token from SQL
        $sql = "SELECT * FROM users WHERE token = :token LIMIT 1";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $token);
        $req->execute();


        if ($req->rowCount() === 1) {
            $this->userData = $req->fetch();
            return true;
        }

        return false;
    }

    /**
     * Login with SAML
     *
     * @param string $email
     * @return bool
     */
    public function loginWithSaml($email)
    {
        if (!$this->populateSession($email)) {
            return false;
        }
        $this->setToken();
        return true;
    }

    /**
     * Check authentication of current user
     *     ____          _
     *    / ___|___ _ __| |__   ___ _ __ _   _ ___
     *   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|
     *   | |___  __/ |  | |_) |  __/ |  | |_| \__ \
     *    \____\___|_|  |_.__/ \___|_|   \__,_|___/
     *
     * @return bool True if we are authentified (or if we don't need to be)
     */
    public function isAuth()
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
            'ResetPasswordController.php'
        );

        if (in_array(basename($this->Request->getScriptName()), $nologinArr)) {
            return true;
        }

        // if we are already logged in with the session, skip everything
        if ($this->Request->getSession()->has('auth')) {
            return true;
        }

        // now try to login with the cookie
        if ($this->loginWithCookie()) {
            // successful login thanks to our cookie friend
            $this->populateSession();
            return true;
        }

        return false;
    }
}
