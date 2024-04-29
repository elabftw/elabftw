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

use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Traits\InsertTagsTrait;
use PDO;

/**
 * All about the experiments
 */
class Experiments extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    public function __construct(Users $users, ?int $id = null)
    {
        $this->page = EntityType::Experiments->value;
        $this->type = EntityType::Experiments->value;
        $this->entityType = EntityType::Experiments;
        parent::__construct($users, $id);
    }

    public function create(int $template = -1, array $tags = array()): int
    {
        $Templates = new Templates($this->Users);
        $Teams = new Teams($this->Users);
        $teamConfigArr = $Teams->readOne();
        $Status = new ExperimentsStatus($Teams);

        // defaults
        $title = _('Untitled');
        $category = null;
        $status = $Status->getDefault();
        $body = null;
        $canread = BasePermissions::Team->toJson();
        $canwrite = BasePermissions::User->toJson();
        $metadata = null;
        $contentType = AbstractEntity::CONTENT_HTML;
        if ($this->Users->userData['use_markdown']) {
            $contentType = AbstractEntity::CONTENT_MD;
        }

        // do we want template ?
        // $templateId can be a template id, or 0: common template, or -1: null body
        if ($template > 0) {
            $Templates->setId($template);
            $templateArr = $Templates->readOne();
            $title = $templateArr['title'];
            $category = $templateArr['category'];
            $status = $templateArr['status'];
            $body = $templateArr['body'];
            $canread = $templateArr['canread_target'];
            $canwrite = $templateArr['canwrite_target'];
            $metadata = $templateArr['metadata'];
            $contentType = (int) $templateArr['content_type'];
        }

        // we don't use a proper template (use of common tpl or blank)
        if ($template === 0 || $template === -1) {
            // if admin forced template use, throw error
            if ($teamConfigArr['force_exp_tpl'] === 1) {
                throw new ImproperActionException(_('Experiments must use a template!'));
            }
            // use user settings for permissions
            $canread = $this->Users->userData['default_read'];
            $canwrite = $this->Users->userData['default_write'];
        }
        // load common template
        if ($template === 0) {
            $commonTemplateKey = 'common_template';
            // use the markdown template if the user prefers markdown
            if ($this->Users->userData['use_markdown']) {
                $commonTemplateKey .= '_md';
            }
            $body = $teamConfigArr[$commonTemplateKey];
        }

        // enforce the permissions if the admin has set them
        $canread = $teamConfigArr['do_force_canread'] === 1 ? $teamConfigArr['force_canread'] : $canread;
        $canwrite = $teamConfigArr['do_force_canwrite'] === 1 ? $teamConfigArr['force_canwrite'] : $canwrite;
        // figure out the custom id
        $customId = $this->getNextCustomId($template);

        // SQL for create experiments
        $sql = 'INSERT INTO experiments(team, title, date, body, category, status, elabid, canread, canwrite, metadata, custom_id, userid, content_type)
            VALUES(:team, :title, CURDATE(), :body, :category, :status, :elabid, :canread, :canwrite, :metadata, :custom_id, :userid, :content_type)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':body', $body, PDO::PARAM_STR);
        $req->bindValue(':category', $category, PDO::PARAM_INT);
        $req->bindValue(':status', $status, PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $canread, PDO::PARAM_STR);
        $req->bindParam(':canwrite', $canwrite, PDO::PARAM_STR);
        $req->bindParam(':metadata', $metadata, PDO::PARAM_STR);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':content_type', $contentType, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();
        $this->setId($newId);

        // insert the tags, steps and links from the template
        if ($template > 0) {
            $Tags = new Tags($Templates);
            $Tags->copyTags($newId, true);
            $this->Steps->duplicate($template, $newId, true);
            $this->ItemsLinks->duplicate($template, $newId, true);
            $Templates->Uploads->duplicate($this);
        }

        $this->insertTags($tags, $newId);

        return $newId;
    }

    /**
     * Duplicate an experiment
     *
     * @return int the ID of the new item
     */
    public function duplicate(): int
    {
        $this->canOrExplode('read');

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $this->entityData['title'] . ' I';

        $Teams = new Teams($this->Users);
        $Status = new ExperimentsStatus($Teams);

        // handle the blank_value_on_duplicate attribute on extra fields
        $metadata = (new Metadata($this->entityData['metadata']))->blankExtraFieldsValueOnDuplicate();
        // figure out the custom id
        $customId = $this->getNextCustomId((int) $this->entityData['category']);

        $sql = 'INSERT INTO experiments(team, title, date, body, category, status, elabid, canread, canwrite, userid, metadata, custom_id, content_type)
            VALUES(:team, :title, CURDATE(), :body, :category, :status, :elabid, :canread, :canwrite, :userid, :metadata, :custom_id, :content_type)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':body', $this->entityData['body'], PDO::PARAM_STR);
        $req->bindValue(':category', $this->entityData['category']);
        $req->bindValue(':status', $Status->getDefault(), PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $this->entityData['canread'], PDO::PARAM_STR);
        $req->bindParam(':canwrite', $this->entityData['canwrite'], PDO::PARAM_STR);
        $req->bindParam(':metadata', $metadata, PDO::PARAM_STR);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':content_type', $this->entityData['content_type'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();
        /** @psalm-suppress PossiblyNullArgument
         * this->id cannot be null here, checked during canOrExplode */
        $this->ExperimentsLinks->duplicate($this->id, $newId);
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);
        // also add a link to the previous experiment
        $ExperimentsLinks = new ExperimentsLinks(new self($this->Users, $newId));
        $ExperimentsLinks->setId($this->id);
        $ExperimentsLinks->postAction(Action::Create, array());

        return $newId;
    }

    /**
     * Experiment is not actually deleted but the state is changed from normal to deleted
     */
    public function destroy(): bool
    {
        // delete from pinned too
        return parent::destroy() && $this->Pins->cleanup();
    }

    protected function getNextCustomId(int $category): ?int
    {
        $sql = 'SELECT custom_id FROM experiments WHERE custom_id IS NOT NULL AND category = :category ORDER BY custom_id DESC LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if ($res === false || $res['custom_id'] === null) {
            return null;
        }
        return ++$res['custom_id'];
    }
}
