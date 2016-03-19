<?php
/**
 * \Elabftw\Elabftw\Admin
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
 * Stuff for admin panel
 */
class Admin
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
     * Only admin can use this
     *
     * @return int 1 if is_admin
     */
    protected function checkPermission()
    {
        return $_SESSION['is_admin'];
    }

    /**
     * Get the body of the default experiment template
     *
     * @return string body of the common template
     */
    public function commonTplRead()
    {
        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id'], \PDO::PARAM_INT);
        $req->execute();
        return $req->fetchColumn();
    }

    /**
     * Update the template
     *
     * @return bool true if sql success
     */
    public function commonTplUpdate($body)
    {
        $body = check_body($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->bindParam(':body', $body);
        return $req->execute();
    }
}
