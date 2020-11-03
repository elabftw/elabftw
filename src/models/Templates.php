<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Elabftw\Traits\SortableTrait;
use function is_bool;
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
     * Create a template
     */
    public function create(ParamsProcessor $params, bool $isDefault = false): int
    {
        $team = $params->team;
        if ($team === 0) {
            $team = $this->Users->userData['team'];
        }
        $userid = $params->id;
        // default template will have userid 0
        if ($userid === 0 && !$isDefault) {
            $userid = $this->Users->userData['userid'];
        }

        $canread = 'team';
        $canwrite = 'user';

        if (isset($this->Users->userData['default_read'])) {
            $canread = $this->Users->userData['default_read'];
        }
        if (isset($this->Users->userData['default_write'])) {
            $canwrite = $this->Users->userData['default_write'];
        }

        $sql = 'INSERT INTO experiments_templates(team, name, body, userid, canread, canwrite) VALUES(:team, :name, :body, :userid, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindParam(':name', $params->name);
        $req->bindParam('body', $params->template);
        $req->bindParam('userid', $userid, PDO::PARAM_INT);
        $req->bindParam('canread', $canread, PDO::PARAM_STR);
        $req->bindParam('canwrite', $canwrite, PDO::PARAM_STR);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
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

        $this->create(new ParamsProcessor(array('name' => 'default', 'template' => $defaultBody, 'id' => 0, 'team' => $team)), true);
    }

    /**
     * Duplicate a template from someone else in the team
     *
     * @return int id of the new template
     */
    public function duplicate(): int
    {
        $template = $this->read();

        $sql = 'INSERT INTO experiments_templates(team, name, body, userid, canread, canwrite) VALUES(:team, :name, :body, :userid, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':name', $template['name']);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam('canread', $template['canread'], PDO::PARAM_STR);
        $req->bindParam('canwrite', $template['canwrite'], PDO::PARAM_STR);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        // copy tags
        $Tags = new Tags($this);
        $Tags->copyTags($newId);

        // copy links and steps too
        $Links = new Links($this);
        $Steps = new Steps($this);
        $Links->duplicate((int) $template['id'], $newId, true);
        $Steps->duplicate((int) $template['id'], $newId, true);

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
        $sql = "SELECT experiments_templates.id, experiments_templates.name, experiments_templates.body,
            experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
            FROM experiments_templates
            LEFT JOIN users ON (experiments_templates.userid = users.userid)
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE experiments_templates.id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('No template found with this id!');
        }

        return $res;
    }

    /**
     * Read the templates for the user (in ucp or create new menu)
     * depending on the user preference, we filter out on the owner or not
     */
    public function readForUser(): array
    {
        if (!$this->Users->userData['show_team_templates']) {
            $this->addFilter('experiments_templates.userid', $this->Users->userData['userid']);
        }
        return $this->getTemplatesList();
    }

    /**
     * Filter the readable templates to only get the ones where we can write to
     * Use this to display templates in UCP
     */
    public function getWriteableTemplatesList(): array
    {
        $TeamGroups = new TeamGroups($this->Users);
        $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();

        return array_filter($this->getTemplatesList(), function ($t) use ($teamgroupsOfUser) {
            return $t['canwrite'] === 'public' || $t['canwrite'] === 'organization' ||
                ($t['canwrite'] === 'team' && ((int) $t['teams_id'] === $this->Users->userData['team'])) ||
                ($t['canwrite'] === 'user' && $t['userid'] === $this->Users->userData['userid']) ||
                (in_array($t['canwrite'], $teamgroupsOfUser, true));
        });
    }

    /**
     * Get a list of fullname + id + name of template
     * Use this to build a select of the readable templates
     */
    public function getTemplatesList(): array
    {
        $TeamGroups = new TeamGroups($this->Users);
        $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();

        $sql = "SELECT DISTINCT experiments_templates.id, experiments_templates.name, experiments_templates.canwrite,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname,
                users2teams.teams_id, experiments_templates.userid, experiments_templates.body
                FROM experiments_templates
                LEFT JOIN users ON (experiments_templates.userid = users.userid)
                LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = :team)
                WHERE experiments_templates.userid != 0 AND (
                    experiments_templates.canread = 'public' OR
                    experiments_templates.canread = 'organization' OR
                    (experiments_templates.canread = 'team' AND users2teams.users_id = experiments_templates.userid) OR
                    (experiments_templates.canread = 'user' AND experiments_templates.userid = :userid)";
        // add all the teamgroups in which the user is
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= " OR (experiments_templates.canread = $teamgroup)";
            }
        }
        $sql .= ')';

        foreach ($this->filters as $filter) {
            $sql .= sprintf(" AND %s = '%s'", $filter['column'], $filter['value']);
        }

        $sql .= 'GROUP BY id ORDER BY fullname, experiments_templates.ordering ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

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
        $this->Db->execute($req);

        $res = $req->fetchColumn();
        if (is_bool($res) || $res === null) {
            return '';
        }
        return (string) $res;
    }

    /**
     * Update the common team template from admin.php
     *
     * @param string $body Content of the template
     * @return void
     */
    public function updateCommon(string $body): void
    {
        if (!$this->Users->userData['is_admin']) {
            throw new IllegalActionException('Non admin user tried to update common template.');
        }
        $sql = "UPDATE experiments_templates SET
            name = 'default',
            team = :team,
            body = :body
            WHERE userid = 0 AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $this->Db->execute($req);
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
        $this->Db->execute($req);
    }

    /**
     * Delete template
     *
     */
    public function destroy(int $id): void
    {
        $sql = 'DELETE FROM experiments_templates WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->Tags->destroyAll();
    }
}
