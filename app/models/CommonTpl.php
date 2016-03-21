<?php
/**
 * \Elabftw\Elabftw\CommonTpl
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
 * The common experiment template for the team
 */
class CommonTpl extends Panel
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
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
    }

    /**
     * Get the body of the default experiment template
     *
     * @param int $team Team ID
     * @return string body of the common template
     */
    public function read($team)
    {
        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Update the template
     *
     * @param string $body Content of the template
     * @param int $team Team ID
     * @return bool true if sql success
     */
    public function update($body, $team)
    {
        $body = check_body($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->bindParam(':body', $body);

        return $req->execute();
    }
}
