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
class Templates extends AbstractEntity
{
    use EntityTrait;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Users $Users instance of Users */
    public $Users;

    /** @var string $type almost the database table… */
    public $type = 'experiments_tpl';

    /**
     * Give me the team on init
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, $id = null)
    {
        parent::__construct($users, $id);
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
        $req = $this->Db->prepare($sql);
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
        $sql = "SELECT name, body, userid FROM experiments_templates WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
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
        $sql = "SELECT experiments_templates.id,
            experiments_templates.body,
            experiments_templates.name,
            GROUP_CONCAT(tagt.tag SEPARATOR '|') as tags, GROUP_CONCAT(tagt.id) as tags_id
            FROM experiments_templates
            LEFT JOIN experiments_tpl_tags AS tagt ON (experiments_templates.id = tagt.item_id)
            WHERE experiments_templates.userid = :userid group by experiments_templates.id ORDER BY experiments_templates.ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid);
        $req->execute();

        return $req->fetchAll();
    }


    /**
     * Get the body of the default experiment template
     *
     * @return string body of the common template
     */
    public function readCommonBody()
    {
        // don't load the common template if you are using markdown because it's probably in html
        if ($this->Users->userData['use_markdown']) {
            return "";
        }

        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Update the common team template from admin.php
     *
     * @param string $body Content of the template
     * @return bool true if sql success
     */
    public function updateCommon($body)
    {
        $body = Tools::checkBody($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Update a template
     *
     * @param int $id Id of the template
     * @param string $name Title of the template
     * @param string $body Content of the template
     * @return bool
     */
    public function update($id, $name, $body)
    {
        $body = Tools::checkBody($body);
        $name = Tools::checkTitle($name);
        $this->setId($id);

        $sql = "UPDATE experiments_templates SET
            name = :name,
            body = :body
            WHERE userid = :userid AND id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Delete template
     *
     * @return bool
     */
    public function destroy()
    {
        $sql = "DELETE FROM experiments_templates WHERE id = :id AND userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':userid', $this->Users->userid);
        $res1 = $req->execute();

        $res2 = $this->Tags->destroyAll();

        return $res1 && $res2;
    }

    /**
     * No category for templates
     *
     * @param int $category
     */
    public function updateCategory($category)
    {
    }

    /**
     * No duplication for templates (yet!)
     *
     */
    public function duplicate()
    {
    }

    /**
     * No locking option for templates
     *
     */
    public function toggleLock()
    {
    }
}
