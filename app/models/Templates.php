<?php
/**
 * \Elabftw\Elabftw\Templates
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * All about the templates
 */
class Templates extends Entity
{
    /** pdo object */
    protected $pdo;

    /** instance of Users */
    public $Users;

    /**
     * Give me the team on init
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, $id = null)
    {
        $this->pdo = Db::getConnection();
        $this->Users = $users;
        if (!is_null($id)) {
            $this->setId($id);
        }
    }

    /**
     * Create a template
     *
     * @param string $name
     * @param string $body
     * @param int $userid
     * @param int|null $team
     * @return bool
     */
    public function create($name, $body, $userid, $team = null)
    {
        if (is_null($team)) {
            $team = $this->Users->userData['team'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $body = Tools::checkBody($body);

        $sql = "INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team);
        $req->bindParam(':name', $name);
        $req->bindParam('body', $body);
        $req->bindParam('userid', $userid);

        return $req->execute();
    }

    /**
     * Create a default template for a new team
     *
     * @param int $team the id of the new team
     * @return bool
     */
    public function createDefault($team)
    {
        $defaultBody = "<p><span style='font-size: 14pt;'><strong>Goal :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style='font-size: 14pt;'><strong>Procedure :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style='font-size: 14pt;'><strong>Results :</strong></span></p><p>&nbsp;</p>";

        return $this->create('default', $defaultBody, 0, $team);
    }

    /**
     * Read a template
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT name, body FROM experiments_templates WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read templates for a user
     *
     * @return array
     */
    public function readFromUserid()
    {
        $sql = "SELECT id, body, name FROM experiments_templates WHERE userid = :userid ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid);
        $req->execute();

        return $req->fetchAll();
    }


    /**
     * Get the body of the default experiment template
     *
     * @return string body of the common template
     */
    public function readCommon()
    {
        $sql = "SELECT * FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Update the common team template from admin.php
     *
     * @param string $body Content of the template
     * @return bool true if sql success
     */
    public function update($body)
    {
        $body = Tools::checkBody($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Delete template
     *
     * @param int $id ID of the template
     * @param int $userid
     * @return bool
     */
    public function destroy($id, $userid)
    {
        $sql = "DELETE FROM experiments_templates WHERE id = :id AND userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }
}
