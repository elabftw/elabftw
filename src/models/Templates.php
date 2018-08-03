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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use PDO;
/**
 * All about the templates
 */
class Templates extends AbstractEntity
{
    use EntityTrait;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->type = 'experiments_tpl';
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
    public function create(string $name, string $body, int $userid, ?int $team = null): bool
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $body = Tools::checkBody($body);

        $sql = "INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindParam(':name', $name);
        $req->bindParam('body', $body);
        $req->bindParam('userid', $userid, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Create a default template for a new team
     *
     * @param int $team the id of the new team
     * @return bool
     */
    public function createDefault(int $team): bool
    {
        $defaultBody = "<p><span style='font-size: 14pt;'><strong>Goal :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style='font-size: 14pt;'><strong>Procedure :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style='font-size: 14pt;'><strong>Results :</strong></span></p><p>&nbsp;</p>";

        return $this->create('default', $defaultBody, 0, $team);
    }

    /**
     * Duplicate a template from someone else in the team
     *
     * @return int id of the new template
     */
    public function duplicate(): int
    {
        $template = $this->read();

        $sql = "INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':name', $template['name']);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->execute();
        $newId = $this->Db->lastInsertId();

        // copy tags
        $Tags = new Tags($this);
        $Tags->copyTags($newId);

        return $newId;
    }

    /**
     * Read a template
     *
     * @return array
     */
    public function read(): array
    {
        $sql = "SELECT name, body, userid FROM experiments_templates WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read templates for a user
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT experiments_templates.id,
            experiments_templates.body,
            experiments_templates.name,
            GROUP_CONCAT(tags.tag SEPARATOR '|') as tags, GROUP_CONCAT(tags.id) as tags_id
            FROM experiments_templates
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_tpl')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE experiments_templates.userid = :userid
            GROUP BY experiments_templates.id ORDER BY experiments_templates.ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Read the templates from the team. Don't take into account the userid = 0 (common templates)
     * nor the current user templates
     *
     * @return array
     */
    public function readFromTeam(): array
    {
        $sql = "SELECT experiments_templates.id,
            experiments_templates.body,
            experiments_templates.name,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            GROUP_CONCAT(tags.tag SEPARATOR '|') as tags, GROUP_CONCAT(tags.id) as tags_id
            FROM experiments_templates
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_tpl')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            LEFT JOIN users ON (experiments_templates.userid = users.userid)
            WHERE experiments_templates.userid != 0 AND experiments_templates.userid != :userid
            AND experiments_templates.team = :team
            GROUP BY experiments_templates.id ORDER BY experiments_templates.ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the body of the default experiment template
     *
     * @return string body of the common template
     */
    public function readCommonBody(): string
    {
        // don't load the common template if you are using markdown because it's probably in html
        if ($this->Users->userData['use_markdown']) {
            return "";
        }

        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Update the common team template from admin.php
     *
     * @param string $body Content of the template
     * @return bool true if sql success
     */
    public function updateCommon(string $body): bool
    {
        $body = Tools::checkBody($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
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
    public function updateTpl(int $id, string $name, string $body): bool
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
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Delete template
     *
     * @return bool
     */
    public function destroy(): bool
    {
        $sql = "DELETE FROM experiments_templates WHERE id = :id AND userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $res1 = $req->execute();

        $res2 = $this->Tags->destroyAll();

        return $res1 && $res2;
    }

    /**
     * No category for templates
     *
     * @param int $category
     */
    public function updateCategory(int $category): bool
    {
        return false;
    }

    /**
     * No locking option for templates
     *
     */
    public function toggleLock(): bool
    {
        return false;
    }
}
