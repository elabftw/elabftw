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
class Users
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

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
        $user = new User();
        if (!$user->checkCredentials($_SESSION['username'], $password)) {
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
}
