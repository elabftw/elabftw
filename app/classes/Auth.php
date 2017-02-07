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

/**
 * Provide methods to login a user
 */
class Auth
{
    /** the minimum password length */
    const MIN_PASSWORD_LENGTH = 8;

    /** Used to store the PDO object */
    protected $pdo;

    /** Everything about the user */
    private $userData;

    /** Token that will be in the cookie + db */
    private $token;

    /**
     * Just give me the Db object and I'm good to go
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
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
        $req = $this->pdo->prepare($sql);
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
        $req = $this->pdo->prepare($sql);
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
     * Store userid and permissions in $_SESSION
     *
     */
    private function populateSession()
    {
        session_regenerate_id();
        $_SESSION['auth'] = 1;
        $_SESSION['userid'] = $this->userData['userid'];
        $_SESSION['team_id'] = $this->userData['team'];
        // Used in the menu
        $_SESSION['firstname'] = $this->userData['firstname'];
        // load permissions
        $perm_sql = "SELECT * FROM groups WHERE group_id = :group_id LIMIT 1";
        $perm_req = $this->pdo->prepare($perm_sql);
        $perm_req->bindParam(':group_id', $this->userData['usergroup']);
        $perm_req->execute();
        $group = $perm_req->fetch(\PDO::FETCH_ASSOC);

        $_SESSION['is_admin'] = $group['is_admin'];
        $_SESSION['is_sysadmin'] = $group['is_sysadmin'];

        // Make a unique token and store it in sql AND cookie
        $this->token = md5(uniqid(rand(), true));
        // and SESSION
        $_SESSION['token'] = $this->token;
        session_write_close();
    }

    /**
     * Set a $_COOKIE['token'] and update the database with this token.
     * Works only in HTTPS, valable for 1 month.
     * 1 month = 60*60*24*30 =  2592000
     *
     */
    private function setToken()
    {
        setcookie('token', $this->token, time() + 2592000, '/', null, true, true);
        // Update the token in SQL
        $sql = "UPDATE users SET token = :token WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':token', $this->token);
        $req->bindParam(':userid', $this->userData['userid']);
        $req->execute();
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
                $this->setToken();
            }
            return true;
        }
        return false;
    }

    /**
     * We are not auth, but maybe we have a cookie, try to login with that
     *
     * @return bool True if we have a valid cookie and it is the same token as in the DB
     */
    public function loginWithCookie()
    {
        // the token is a md5 sum
        if (!isset($_COOKIE['token']) || strlen($_COOKIE['token']) != 32) {
            return false;
        }
        // If user has a cookie; check cookie is valid
        $token = filter_var($_COOKIE['token'], FILTER_SANITIZE_STRING);
        // Get token from SQL
        $sql = "SELECT * FROM users WHERE token = :token LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':token', $token);
        $req->execute();

        $this->userData = $req->fetch();

        if ($req->rowCount() === 1) {
            $this->populateSession();
            return true;
        }

        return false;
    }
}
