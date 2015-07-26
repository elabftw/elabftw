<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
namespace Elabftw\Elabftw;

use \Elabftw\Elabftw\Db;

class User
{
    private $pdo;

    private $salt;
    private $userData;
    private $token;

    public function __construct()
    {
        $db = new \Elabftw\Elabftw\Db();
        $this->pdo = $db->connect();
    }

    /*
     * Get the salt for the user so we can generate a correct hash
     *
     */
    private function setSalt($username)
    {
        $sql = "SELECT salt FROM users WHERE username = :username LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':username', $username);
        $req->execute();
        $this->salt = $req->fetchColumn();

    }

    /*
     * Test username and password in the database
     *
     * @return bool True if the login + password are good
     */
    private function checkCredentials($username, $password)
    {
        $this->setSalt($username);

        $passwordHash = hash('sha512', $this->salt . $password);

        $sql = "SELECT * FROM users WHERE username = :username AND password = :passwordHash AND validated= 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':username', $username);
        $req->bindParam(':passwordHash', $passwordHash);
        //Check whether the query was successful or not
        if ($req->execute() && $req->rowCount() === 1) {
            // populate the userData
            $this->userData = $req->fetch();
            return true;
        }
        return false;
    }

    /*
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
        $_SESSION['username'] = $this->userData['username'];
        // load permissions
        $perm_sql = "SELECT * FROM groups WHERE group_id = :group_id LIMIT 1";
        $perm_req = $this->pdo->prepare($perm_sql);
        $perm_req->bindParam(':group_id', $this->userData['usergroup']);
        $perm_req->execute();
        $group = $perm_req->fetch(\PDO::FETCH_ASSOC);

        $_SESSION['is_admin'] = $group['is_admin'];
        $_SESSION['is_sysadmin'] = $group['is_sysadmin'];

        // PREFS
        $_SESSION['prefs'] = array(
            'display' => $this->userData['display'],
            'order' => $this->userData['order_by'],
            'sort' => $this->userData['sort_by'],
            'limit' => $this->userData['limit_nb'],
            'shortcuts' => array('create' => $this->userData['sc_create'], 'edit' => $this->userData['sc_edit'], 'submit' => $this->userData['sc_submit'], 'todo' => $this->userData['sc_todo']),
            'lang' => $this->userData['lang'],
            'close_warning' => intval($this->userData['close_warning']),
            'chem_editor' => intval($this->userData['chem_editor']));
        // Make a unique token and store it in sql AND cookie
        $this->token = md5(uniqid(rand(), true));
        // and SESSION
        $_SESSION['token'] = $this->token;
        session_write_close();
    }

    /*
     * Set a $_COOKIE['token'] and update the database with this token.
     * Works only in HTTPS, valable for 1 month.
     * 1 month = 60*60*24*30 =  2592000
     *
     */
    private function setToken()
    {
        // setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly )
        setcookie('token', $this->token, time() + 2592000, '/', null, true, true);
        // Update the token in SQL
        $sql = "UPDATE users SET token = :token WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':token', $this->token);
        $req->bindParam(':userid', $this->userData['userid']);
        $req->execute();

    }

    /**
     * Login with username and password
     *
     * @return bool Return true if user provided correct credentials
     */
    public function login($username, $password)
    {
        if ($this->checkCredentials($username, $password)) {
            $this->populateSession();
            $this->setToken();
            return true;
        }
        return false;
    }

    /*
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
