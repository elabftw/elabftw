<?php
/**
 * \Elabftw\Elabftw\Users
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * Users
 */
class Users extends Auth
{
    /**
     * Get info about a user
     *
     * @param int $userid
     */
    public function read($userid)
    {
        $sql = 'SELECT * FROM users WHERE userid = :userid';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Update user
     *
     */
    public function update($userid, $firstname, $lastname, $username, $email, $validated, $usergroup, $password)
    {
        $userid = Tools::checkId($userid);
        if ($userid === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        // permission check
        if (!isset($_SESSION['is_admin'])) {
            throw new Exception(_('This section is out of your reach.'));
        }

        // Put everything lowercase and first letter uppercase
        $firstname = ucwords(strtolower(filter_var($firstname, FILTER_SANITIZE_STRING)));
        // Lastname in uppercase
        $lastname = strtoupper(filter_var($lastname, FILTER_SANITIZE_STRING));
        $username = filter_var($username, FILTER_SANITIZE_STRING);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if ($validated == 1) {
            $validated = 1;
        } else {
            $validated = 0;
        }
        $usergroup = Tools::checkId($usergroup);
        if ($usergroup === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        // a non sysadmin cannot put someone sysadmin
        if ($usergroup == 1 && $_SESSION['is_sysadmin'] != 1) {
            throw new Exception(_('Only a sysadmin can put someone sysadmin.'));
        }

        if (strlen($password) > 1) {
            $this->updatePassword($password, $userid);
        }

        $sql = "UPDATE users SET
            firstname = :firstname,
            lastname = :lastname,
            username = :username,
            email = :email,
            usergroup = :usergroup,
            validated = :validated
            WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':username', $username);
        $req->bindParam(':email', $email);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':usergroup', $usergroup);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }

    /**
     * Update the password for a user, or for ourself if none provided
     *
     * @param string $password The new password
     * @param int|null $userid The user we want to update
     * @throws Exception if invalid character length
     * @return bool True if password is updated
     */
    public function updatePassword($password, $userid = null)
    {

        if (is_null($userid)) {
            $userid = $_SESSION['userid'];
        }
        if (!$this->checkPasswordLength($password)) {
            $error = sprintf(_('Password must contain at least %s characters.'), self::MIN_PASSWORD_LENGTH);
            throw new Exception($error);
        }
        // Create a new salt
        $salt = hash("sha512", uniqid(rand(), true));
        $passwordHash = hash("sha512", $salt . $password);

        $sql = "UPDATE users SET salt = :salt, password = :password WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':salt', $salt);
        $req->bindParam(':password', $passwordHash);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }

    /**
     * Destroy user. Will completely remove everything from the user.
     *
     * @param string $email The email of the user we want to delete
     * @param string $password The confirmation password
     * @return bool
     */
    public function destroy($email, $password)
    {
        // check that we got the good password
        if (!$this->checkCredentials($_SESSION['username'], $password)) {
            throw new Exception(_("Wrong password!"));
        }
        // check the user is in our team and also get the userid
        $userid = $this->emailInTeam($email, $_SESSION['team_id']);

        if (!$userid) {
            throw new Exception(_('No user with this email or user not in your team'));
        }

        $result = array();

        $sql = "DELETE FROM users WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        $sql = "DELETE FROM experiments_tags WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        $sql = "DELETE FROM experiments WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        // get all filenames
        $sql = "SELECT long_name FROM uploads WHERE userid = :userid AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid,
            'type' => 'experiments'
        ));
        while ($uploads = $req->fetch()) {
            // Delete file
            $filepath = ELAB_ROOT . 'uploads/' . $uploads['long_name'];
            $result[] = unlink($filepath);
        }

        $sql = "DELETE FROM uploads WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        return !in_array(0, $result);
    }

    /**
     * Check if a user is in our team
     *
     * @param string $email
     * @param int $team
     * @return int|bool
     */
    private function emailInTeam($email, $team)
    {
        $sql = "SELECT userid FROM users WHERE email LIKE :email AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':email', $email);
        $req->bindParam(':team', $team);
        $req->execute();

        return $req->fetch();
    }
}
