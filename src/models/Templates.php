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

use DateTimeImmutable;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Services\Filter;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the templates
 */
class Templates extends AbstractTemplateEntity
{
    use SortableTrait;

    public const string defaultBody = '<h1>Goal:</h1>
    <p>&nbsp;</p>
    <h1>Procedure:</h1>
    <p>&nbsp;</p>
    <h1>Results:</h1>
    <p>&nbsp;</p>';

    public const string defaultBodyMd = "# Goal\n\n# Procedure\n\n# Results\n\n";

    public string $page = 'ucp';

    public EntityType $entityType = EntityType::Templates;

    public function create(
        ?int $template = -1,
        ?string $title = null,
        ?string $body = null,
        ?DateTimeImmutable $date = null,
        ?string $canread = null,
        ?string $canwrite = null,
        array $tags = array(),
        ?int $category = null,
        ?int $status = null,
        ?int $customId = null,
        ?string $metadata = null,
        int $rating = 0,
        bool $forceExpTpl = false,
        string $defaultTemplateHtml = '',
        string $defaultTemplateMd = '',
    ): int {
        $title = Filter::title($title ?? _('Untitled'));
        $canread = BasePermissions::Team->toJson();
        $canwrite = BasePermissions::User->toJson();

        if (isset($this->Users->userData['default_read'])) {
            $canread = $this->Users->userData['default_read'];
        }
        if (isset($this->Users->userData['default_write'])) {
            $canwrite = $this->Users->userData['default_write'];
        }
        $contentType = self::CONTENT_HTML;
        if ($this->Users->userData['use_markdown'] === 1) {
            $contentType = self::CONTENT_MD;
        }

        $sql = 'INSERT INTO experiments_templates(team, title, userid, canread, canwrite, canread_target, canwrite_target, content_type, rating)
            VALUES(:team, :title, :userid, :canread, :canwrite, :canread_target, :canwrite_target, :content_type, :rating)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':title', $title);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_target', $canread);
        $req->bindParam(':canwrite_target', $canwrite);
        $req->bindParam(':content_type', $contentType, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->execute();
        $id = $this->Db->lastInsertId();

        // now pin the newly created template so it directly appears in Create menu
        $this->setId($id);
        $Pins = new Pins($this);
        $Pins->togglePin();
        return $id;
    }

    /**
     * Duplicate a template from someone else in the team
     */
    public function duplicate(bool $copyFiles = false): int
    {
        $template = $this->readOne();

        $sql = 'INSERT INTO experiments_templates(team, title, category, status, body, userid, canread, canwrite, canread_target, canwrite_target, metadata)
            VALUES(:team, :title, :category, :status, :body, :userid, :canread, :canwrite, :canread_target, :canwrite_target, :metadata)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':title', $template['title']);
        $req->bindParam(':body', $template['body']);
        $req->bindParam(':category', $template['category']);
        $req->bindParam(':status', $template['status']);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':canread', $template['canread']);
        $req->bindParam(':canwrite', $template['canwrite']);
        $req->bindParam(':canread_target', $template['canread_target']);
        $req->bindParam(':canwrite_target', $template['canwrite_target']);
        $req->bindParam(':metadata', $template['metadata']);
        $req->execute();
        $newId = $this->Db->lastInsertId();

        // copy tags
        $Tags = new Tags($this);
        $Tags->copyTags($newId);

        // copy links and steps too
        $ItemsLinks = new ExperimentsTemplates2ItemsLinks($this);
        $ItemsLinks->duplicate($template['id'], $newId, true);
        $ExperimentsLinks = new ExperimentsTemplates2ExperimentsLinks($this);
        $ExperimentsLinks->duplicate($template['id'], $newId, true);
        $Steps = new Steps($this);
        $Steps->duplicate($template['id'], $newId, true);

        // now pin the newly created template so it directly appears in Create menu
        $this->setId($newId);
        $Pins = new Pins($this);
        $Pins->togglePin();

        return $newId;
    }

    public function readOne(): array
    {
        $sql = "SELECT experiments_templates.id, experiments_templates.title, experiments_templates.body,
                experiments_templates.created_at, experiments_templates.modified_at, experiments_templates.content_type,
                experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite,
                experiments_templates.canread_target, experiments_templates.canwrite_target,
                experiments_templates.locked, experiments_templates.lockedby, experiments_templates.locked_at,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata, experiments_templates.state,
                users.firstname, users.lastname, users.orcid,
                experiments_templates.category,experiments_templates.status,
                categoryt.title AS category_title, categoryt.color AS category_color, statust.title AS status_title, statust.color AS status_color,
                GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
            FROM experiments_templates
            LEFT JOIN users
                ON (experiments_templates.userid = users.userid)
            LEFT JOIN tags2entity
                ON (experiments_templates.id = tags2entity.item_id
                    AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags
                ON (tags2entity.tag_id = tags.id)
            LEFT JOIN experiments_categories AS categoryt
                ON (experiments_templates.category = categoryt.id)
            LEFT JOIN experiments_status AS statust
                ON (experiments_templates.status = statust.id)
            WHERE experiments_templates.id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->entityData = $this->Db->fetch($req);
        if ($this->entityData['id'] === null) {
            throw new ResourceNotFoundException();
        }
        $this->canOrExplode('read');
        // add steps and links in there too
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['experiments_links'] = $this->ExperimentsLinks->readAll();
        $this->entityData['items_links'] = $this->ItemsLinks->readAll();
        $this->entityData['sharelink'] = sprintf(
            '%s/%s&mode=view&templateid=%d',
            Config::fromEnv('SITE_URL'),
            EntityType::Templates->toPage(),
            $this->id
        );
        // add the body as html
        $this->entityData['body_html'] = $this->entityData['body'];
        // convert from markdown only if necessary
        if ($this->entityData['content_type'] === self::CONTENT_MD) {
            $this->entityData['body_html'] = Tools::md2html($this->entityData['body'] ?? '');
        }
        if (!empty($this->entityData['metadata'])) {
            $this->entityData['metadata_decoded'] = json_decode($this->entityData['metadata']);
        }
        $this->entityData['uploads'] = $this->Uploads->readAll();
        $this->entityData['exclusive_edit_mode'] = $this->ExclusiveEditMode->readOne();
        return $this->entityData;
    }

    /**
     * Get a list of fullname + id + title of template
     * Use this to build a select of the readable templates
     */
    public function readAll(): array
    {
        $sql = array();
        $sql[] = "SELECT DISTINCT experiments_templates.id, experiments_templates.title, experiments_templates.body,
                experiments_templates.userid, experiments_templates.canread, experiments_templates.canwrite, experiments_templates.content_type,
                experiments_templates.locked, experiments_templates.lockedby, experiments_templates.locked_at,
                experiments_templates.canread_target, experiments_templates.canwrite_target,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname, experiments_templates.metadata, experiments_templates.modified_at,
                users2teams.teams_id, teams.name AS team_name,
                (pin_experiments_templates2users.entity_id IS NOT NULL) AS is_pinned,
                experiments_templates.category,experiments_templates.status,
                categoryt.title AS category_title, categoryt.color AS category_color, statust.title AS status_title, statust.color AS status_color,
                GROUP_CONCAT(tags.tag SEPARATOR '|') AS tags, GROUP_CONCAT(tags.id) AS tags_id
            FROM experiments_templates
            LEFT JOIN users ON (experiments_templates.userid = users.userid)
            LEFT JOIN users2teams
                ON (users2teams.users_id = users.userid
                    AND users2teams.teams_id = :team)
            LEFT JOIN teams ON (teams.id = experiments_templates.team)
            LEFT JOIN tags2entity
                ON (experiments_templates.id = tags2entity.item_id
                    AND tags2entity.item_type = 'experiments_templates')
            LEFT JOIN tags
                ON (tags2entity.tag_id = tags.id)
            LEFT JOIN experiments_categories AS categoryt
                ON (experiments_templates.category = categoryt.id)
            LEFT JOIN experiments_status AS statust
                ON (experiments_templates.status = statust.id)
            LEFT JOIN pin_experiments_templates2users
                ON (experiments_templates.id = pin_experiments_templates2users.entity_id
                    AND pin_experiments_templates2users.users_id = :userid)
            WHERE experiments_templates.userid != 0
                AND experiments_templates.state = :state";

        $canSql = array();
        $canSql[] = sprintf(
            "experiments_templates.canread->'$.base' = %d",
            BasePermissions::Full->value,
        );
        $canSql[] = sprintf(
            "experiments_templates.canread->'$.base' = %d",
            BasePermissions::Organization->value,
        );
        $canSql[] = sprintf(
            "experiments_templates.canread->'$.base' = %d AND users2teams.users_id = experiments_templates.userid",
            BasePermissions::Team->value,
        );
        $canSql[] = sprintf(
            "experiments_templates.canread->'$.base' = %d AND experiments_templates.userid = :userid",
            BasePermissions::User->value,
        );
        $canSql[] = sprintf(
            "experiments_templates.canread->'$.base' = %d AND experiments_templates.userid = :userid",
            BasePermissions::UserOnly->value,
        );
        // look for teams
        $teamsOfUser = (new UsersHelper($this->Users->userData['userid']))->getTeamsIdFromUserid();
        if (!empty($teamsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            $canSql[] = sprintf(
                "JSON_OVERLAPS(experiments_templates.canread->'$.teams', CAST('[%s]' AS JSON))",
                implode(', ', $teamsOfUser),
            );
        }
        // look for teamgroups
        $teamgroupsOfUser = array_column((new TeamGroups($this->Users))->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            $canSql[] = sprintf(
                "JSON_OVERLAPS(experiments_templates.canread->'$.teamgroups', CAST('[%s]' AS JSON))",
                implode(', ', $teamgroupsOfUser),
            );
        }
        // look for our userid in users part of the json
        $canSql[] = ':userid MEMBER OF (experiments_templates.canread->>"$.users")';

        $sql[] = sprintf(
            ' AND (%s)',
            implode(' OR ', $canSql),
        );

        if ($this->Users->userData['scope_experiments_templates'] === Scope::User->value) {
            $sql[] = 'AND experiments_templates.userid = :userid';
        }
        if ($this->Users->userData['scope_experiments_templates'] === Scope::Team->value) {
            $sql[] = 'AND experiments_templates.team = :team';
        }

        $sql[] = $this->filterSql;

        $sql[] = str_replace('entity', 'experiments_templates', $this->idFilter);

        $sql[] = 'GROUP BY id ORDER BY experiments_templates.created_at DESC, fullname DESC, is_pinned DESC, experiments_templates.ordering ASC';

        $req = $this->Db->prepare(implode(' ', $sql));
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function destroy(): bool
    {
        // delete from pinned too
        return parent::destroy() && $this->Pins->cleanup();
    }
}
