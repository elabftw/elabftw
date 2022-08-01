<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the templates
 */
class Templates extends AbstractTemplateEntity
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
        $this->type = parent::TYPE_TEMPLATES;
        parent::__construct($users, $id);
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

        $sql = 'INSERT INTO experiments_templates(team, title, body, userid, canread, canwrite)
            VALUES(:team, :title, :body, :userid, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':title', $params->getContent());
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

        $sql = 'INSERT INTO experiments_templates(team, title, body, userid, canread, canwrite, metadata)
            VALUES(:team, :title, :body, :userid, :canread, :canwrite, :metadata)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':title', $template['title']);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':canread', $template['canread']);
        $req->bindParam(':canwrite', $template['canwrite']);
        $req->bindParam(':metadata', $template['metadata']);
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
        return $this->readOne();
    }

    public function readOne(): array
    {
        $sql = "SELECT experiments_templates.id, experiments_templates.title, experiments_templates.body,
            experiments_templates.created_at, experiments_templates.modified_at,
            experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
            experiments_templates.locked, experiments_templates.lockedby, experiments_templates.lockedwhen,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata, experiments_templates.state,
            users.firstname, users.lastname, users.orcid,
            GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
            FROM experiments_templates
            LEFT JOIN users ON (experiments_templates.userid = users.userid)
            LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE experiments_templates.id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->entityData = $this->Db->fetch($req);
        $this->canOrExplode('read');
        // add steps and links in there too
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['links'] = $this->Links->readAll();
        return $this->entityData;
    }

    /**
     * Filter the readable templates to only get the ones where we can write to
     * Use this to display templates in UCP
     */
    public function getWriteableTemplatesList(): array
    {
        $TeamGroups = new TeamGroups($this->Users);
        $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');

        return array_filter($this->readAll(), function ($t) use ($teamgroupsOfUser) {
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
    public function readAll(): array
    {
        $TeamGroups = new TeamGroups($this->Users);
        $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');

        $sql = "SELECT DISTINCT experiments_templates.id, experiments_templates.title, experiments_templates.body,
                experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
                experiments_templates.locked, experiments_templates.lockedby, experiments_templates.lockedwhen,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata,
                users2teams.teams_id,
                (pin_experiments_templates2users.entity_id IS NOT NULL) AS is_pinned,
                GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
                FROM experiments_templates
                LEFT JOIN users ON (experiments_templates.userid = users.userid)
                LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = :team)
                LEFT JOIN tags2entity ON (experiments_templates.id = tags2entity.item_id AND tags2entity.item_type = 'experiments_templates')
                LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
                LEFT JOIN pin_experiments_templates2users ON (experiments_templates.id = pin_experiments_templates2users.entity_id)
                WHERE experiments_templates.userid != 0 AND experiments_templates.state = :state AND (
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

        $sql .= 'GROUP BY id ORDER BY fullname DESC, is_pinned DESC, experiments_templates.ordering ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', self::STATE_NORMAL, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Read the templates for the user (in ucp or create new menu)
     * depending on the user preference, we filter out on the owner or not
     */
    public function readForUser(): array
    {
        if ($this->Users->userData['show_team_templates'] === '0') {
            $this->addFilter('experiments_templates.userid', $this->Users->userData['userid']);
        }
        return $this->readAll();
    }

    public function destroy(): bool
    {
        // delete from pinned too
        return parent::destroy() && $this->Pins->cleanup();
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
