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

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the templates
 */
class Templates extends AbstractEntity
{
    use SortableTrait;

    public const defaultBody = "<h1><span style='font-size: 14pt;'>Goal :</span></h1>
    <p>&nbsp;</p>
    <h1><span style='font-size: 14pt;'>Procedure :</span></h1>
    <p>&nbsp;</p>
    <h1><span style='font-size: 14pt;'>Results :<br /></span></h1>
    <p>&nbsp;</p>";

    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->type = 'experiments_templates';
    }

    public function create(EntityParamsInterface $params): int
    {
        $canread = 'team';
        $canwrite = 'user';

        if (isset($this->Users->userData['default_read'])) {
            $canread = $this->Users->userData['default_read'];
        }
        if (isset($this->Users->userData['default_write'])) {
            $canwrite = $this->Users->userData['default_write'];
        }

        $date = Filter::kdate();
        $sql = 'INSERT INTO experiments_templates(team, title, date, body, userid, canread, canwrite)
            VALUES(:team, :title, :date, :body, :userid, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':title', $params->getContent());
        $req->bindParam(':date', $date);
        $req->bindValue(':body', $params->getExtraBody());
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->execute();
        return $this->Db->lastInsertId();
    }

    /**
     * Duplicate a template from someone else in the team
     */
    public function duplicate(): int
    {
        $template = $this->read(new ContentParams());

        $date = Filter::kdate();
        $sql = 'INSERT INTO experiments_templates(team, title, date, body, userid, canread, canwrite)
            VALUES(:team, :title, :date, :body, :userid, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':title', $template['title']);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':canread', $template['canread']);
        $req->bindParam(':canwrite', $template['canwrite']);
        $req->execute();
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
     */
    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'list') {
            return $this->getList();
        }

        $sql = "SELECT experiments_templates.id, experiments_templates.title, experiments_templates.body,
            experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
            experiments_templates.locked, experiments_templates.lockedby, experiments_templates.lockedwhen,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata,
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
        $this->entityData = $res;

        return $res;
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
                ($t['canwrite'] === 'useronly' && $t['userid'] === $this->Users->userData['userid']) ||
                (in_array($t['canwrite'], $teamgroupsOfUser, true));
        });
    }

    /**
     * Get a list of fullname + id + title of template
     * Use this to build a select of the readable templates
     */
    public function getTemplatesList(): array
    {
        $TeamGroups = new TeamGroups($this->Users);
        $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();

        $sql = "SELECT DISTINCT experiments_templates.id, experiments_templates.title, experiments_templates.body,
                experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
                experiments_templates.locked, experiments_templates.lockedby, experiments_templates.lockedwhen,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata,
                users2teams.teams_id,
                GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
                FROM experiments_templates
                LEFT JOIN users ON (experiments_templates.userid = users.userid)
                LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = :team)
                LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
                LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
                WHERE experiments_templates.userid != 0 AND (
                    experiments_templates.canread = 'public' OR
                    experiments_templates.canread = 'organization' OR
                    (experiments_templates.canread = 'team' AND users2teams.users_id = experiments_templates.userid) OR
                    (experiments_templates.canread = 'user' AND experiments_templates.userid = :userid) OR
                    (experiments_templates.canread = 'useronly' AND experiments_templates.userid = :userid)";
        foreach ($teamgroupsOfUser as $teamgroup) {
            $sql .= " OR (experiments_templates.canread = $teamgroup)";
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
     * Delete template
     */
    public function destroy(): bool
    {
        $this->canOrExplode('write');
        $sql = 'DELETE FROM experiments_templates WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Tags->destroyAll();
    }

    /**
     * Read the templates for the user (in ucp or create new menu)
     * depending on the user preference, we filter out on the owner or not
     */
    public function readForUser(): array
    {
        if (empty($this->Users->userData['userid'])) {
            return array();
        }
        if (!$this->Users->userData['show_team_templates']) {
            $this->addFilter('experiments_templates.userid', $this->Users->userData['userid']);
        }
        return $this->getTemplatesList();
    }

    /**
     * Build a list for tinymce Insert template... menu
     */
    private function getList(): array
    {
        $templates = $this->readForUser();
        $res = array();
        foreach ($templates as $template) {
            $res[] = array('title' => $template['title'], 'description' => '', 'content' => $template['body']);
        }
        return $res;
    }
}
