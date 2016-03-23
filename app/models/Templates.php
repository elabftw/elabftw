<?php
/**
 * \Elabftw\Elabftw\Templates
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;

/**
 * All about the templates
 */
class Templates
{
    /** pdo object */
    private $pdo;

    /**
     * Give me the team on init
     *
     * @param int $team
     */
    public function __construct($team)
    {
        $this->pdo = Db::getConnection();
        $this->team = $team;
    }

    /**
     * Read a template
     *
     * @param int $id
     * @return array
     */
    public function read($id)
    {
        $sql = "SELECT name, body FROM experiments_templates WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->team);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Get the body of the default experiment template
     *
     * @return string body of the common template
     */
    public function readCommon()
    {
        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Update the template
     *
     * @param string $body Content of the template
     * @return bool true if sql success
     */
    public function update($body)
    {
        $body = check_body($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':body', $body);

        return $req->execute();
    }
}
