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
use Elabftw\Elabftw\TemplatesSqlBuilder;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Services\Filter;
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
        ?int $contentType = null,
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
        $contentType ??= $this->Users->userData['use_markdown'] === 1 ? AbstractEntity::CONTENT_MD : AbstractEntity::CONTENT_HTML;

        $sql = 'INSERT INTO experiments_templates(team, title, body, userid, canread, canwrite, canread_target, canwrite_target, content_type, rating)
            VALUES(:team, :title, :body, :userid, :canread, :canwrite, :canread_target, :canwrite_target, :content_type, :rating)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindParam(':body', $body);
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
        $fresh = new self($this->Users, $id);
        $Pins = new Pins($fresh);
        $Pins->togglePin();
        return $id;
    }

    /**
     * Duplicate a template from someone else
     */
    public function duplicate(bool $copyFiles = false): int
    {
        $this->canOrExplode('read');
        $title = $this->entityData['title'] . ' I';
        $newId = $this->create(
            title: $title,
            body: $this->entityData['body'],
            category: $this->entityData['category'],
            status: $this->entityData['status'],
            canread: $this->entityData['canread'],
            canwrite: $this->entityData['canwrite'],
            metadata: $this->entityData['metadata'],
            contentType: $this->entityData['content_type'],
        );
        // add missing can*_target
        $fresh = new self($this->Users, $newId);
        $fresh->patch(Action::Update, array(
            'canread_target' => $this->entityData['canread_target'],
            'canwrite_target' => $this->entityData['canwrite_target'],
        ));

        // copy tags
        $Tags = new Tags($this);
        $Tags->copyTags($newId);

        // copy links and steps too
        $ItemsLinks = new ExperimentsTemplates2ItemsLinks($this);
        /** @psalm-suppress PossiblyNullArgument */
        $ItemsLinks->duplicate($this->id, $newId, true);
        $ExperimentsLinks = new ExperimentsTemplates2ExperimentsLinks($this);
        $ExperimentsLinks->duplicate($this->id, $newId, true);
        $Steps = new Steps($this);
        $Steps->duplicate($this->id, $newId, true);
        if ($copyFiles) {
            $this->Uploads->duplicate($fresh);
        }

        // now pin the newly created template so it directly appears in Create menu
        $Pins = new Pins($fresh);
        $Pins->togglePin();

        return $newId;
    }

    public function readOne(): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }
        $builder = new TemplatesSqlBuilder($this);
        $sql = $builder->getReadSqlBeforeWhere(getTags: true, fullSelect: true);
        $sql .= sprintf(' WHERE entity.id = %d', $this->id);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->entityData = $this->Db->fetch($req);
        // this is needed because the query will return something with everything null instead of throwing the exception at fetch()
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
        $builder = new TemplatesSqlBuilder($this);
        $sql = $builder->getReadSqlBeforeWhere(getTags: false, fullSelect: false);
        // first WHERE is the state, possibly including archived
        // also add a check for no userid 0 which is the common template (this will need to go away!!)
        $sql .= sprintf(' WHERE entity.state = %d AND entity.userid != 0', State::Normal->value);
        // add the json permissions
        $sql .= $builder->getCanFilter('canread');
        if ($this->Users->userData['scope_experiments_templates'] === Scope::User->value) {
            $sql .= 'AND entity.userid = :userid';
        }
        if ($this->Users->userData['scope_experiments_templates'] === Scope::Team->value) {
            $sql .= 'AND entity.team = :team';
        }
        $sql .= $this->idFilter;

        $sql .= ' GROUP BY id ORDER BY entity.created_at DESC, fullname DESC, is_pinned DESC, entity.ordering ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function destroy(): bool
    {
        // delete from pinned too
        return parent::destroy() && $this->Pins->cleanup();
    }
}
