<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the templates
 */
class Templates extends AbstractEntity
{
    use SortableTrait;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->type = 'experiments_templates';
    }

    /**
     * The create function from abstract class in not implemented here
     *
     * @param int $id
     * @return int
     */
    public function create(int $id): int
    {
        return $id;
    }

    /**
     * Create a template
     *
     * @param string $name
     * @param string $body
     * @param int|null $userid
     * @param int|null $team
     * @return void
     */
    public function createNew(string $name, string $body, ?int $userid = null, ?int $team = null): void
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }
        if ($userid === null) {
            $userid = $this->Users->userData['userid'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $body = Filter::body($body);

        $sql = 'INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindParam(':name', $name);
        $req->bindParam('body', $body);
        $req->bindParam('userid', $userid, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Create a default template for a new team
     *
     * @param int $team the id of the new team
     * @return void
     */
    public function createDefault(int $team): void
    {
        $defaultBody = "<h1><span style='font-size: 14pt;'>Goal :</span></h1>
            <p>&nbsp;</p>
            <h1><span style='font-size: 14pt;'>Procedure :</span></h1>
            <p>&nbsp;</p>
            <h1><span style='font-size: 14pt;'>Results :<br /></span></h1>
            <p>&nbsp;</p>";

        $this->createNew('default', $defaultBody, 0, $team);
    }

    /**
     * Duplicate a template from someone else in the team
     *
     * @return int id of the new template
     */
    public function duplicate(): int
    {
        $template = $this->read();

        $sql = 'INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':name', $template['name']);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
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
     * @param bool $getTags
     * @param bool $inTeam
     * @return array
     */
    public function read(bool $getTags = false, bool $inTeam = true): array
    {
        $sql = 'SELECT name, body, userid FROM experiments_templates WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('No template found with this id!');
        }

        return $res;
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
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE experiments_templates.userid = :userid
            GROUP BY experiments_templates.id ORDER BY experiments_templates.ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
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
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            LEFT JOIN users ON (experiments_templates.userid = users.userid)
            WHERE experiments_templates.userid != 0 AND experiments_templates.userid != :userid
            AND experiments_templates.team = :team
            GROUP BY experiments_templates.id ORDER BY experiments_templates.ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
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
            return '';
        }

        $sql = 'SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return $res;
    }

    /**
     * Update the common team template from admin.php
     *
     * @param string $body Content of the template
     * @return void
     */
    public function updateCommon(string $body): void
    {
        $body = Filter::body($body);
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Update a template
     *
     * @param int $id Id of the template
     * @param string $name Title of the template
     * @param string $body Content of the template
     * @return void
     */
    public function updateTpl(int $id, string $name, string $body): void
    {
        $body = Filter::body($body);
        $name = Filter::title($name);
        $this->setId($id);

        $sql = 'UPDATE experiments_templates SET
            name = :name,
            body = :body
            WHERE userid = :userid AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Delete template
     *
     * @return void
     */
    public function destroy(): void
    {
        $sql = 'DELETE FROM experiments_templates WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $this->Tags->destroyAll();
    }
}
