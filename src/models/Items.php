<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Traits\InsertTagsTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the database items
 */
class Items extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    public function __construct(Users $users, ?int $id = null)
    {
        $this->type = EntityType::Items->value;
        $this->entityType = EntityType::Items;
        $this->page = 'database';
        parent::__construct($users, $id);
    }

    // special case for items where the page property is not the correct endpoint
    public function getPage(): string
    {
        return sprintf('api/v2/%s/', EntityType::Items->value);
    }

    public function create(int $template, array $tags = array()): int
    {
        $ItemsTypes = new ItemsTypes($this->Users, $template);
        $itemTemplate = $ItemsTypes->readOne();
        // figure out the custom id
        $customId = $this->getNextCustomId($template);

        $sql = 'INSERT INTO items(team, title, date, status, body, userid, category, elabid, canread, canwrite, canbook, metadata, custom_id)
            VALUES(:team, :title, CURDATE(), :status, :body, :userid, :category, :elabid, :canread, :canwrite, :canread, :metadata, :custom_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':title', _('Untitled'), PDO::PARAM_STR);
        $req->bindParam(':status', $itemTemplate['status'], PDO::PARAM_STR);
        $req->bindParam(':body', $itemTemplate['body'], PDO::PARAM_STR);
        $req->bindParam(':category', $template, PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $itemTemplate['canread_target'], PDO::PARAM_STR);
        $req->bindParam(':canwrite', $itemTemplate['canwrite_target'], PDO::PARAM_STR);
        $req->bindParam(':metadata', $itemTemplate['metadata'], PDO::PARAM_STR);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);
        $this->ItemsLinks->duplicate($itemTemplate['id'], $newId, true);
        $this->Steps->duplicate($itemTemplate['id'], $newId, true);

        return $newId;
    }

    /**
     * Get all items with is_bookable that we can read
     */
    public function readBookable(): array
    {
        $Request = Request::createFromGlobals();
        $DisplayParams = new DisplayParams($this->Users, $Request, EntityType::Items);
        // we only want the bookable type of items
        $DisplayParams->appendFilterSql(FilterableColumn::Bookable, 1);
        // make limit very big because we want to see ALL the bookable items here
        $DisplayParams->limit = 900000;
        // filter on the canbook or canread depending on query param
        if ($Request->query->has('canbook')) {
            return $this->readShow($DisplayParams, true, 'canbook');
        }
        return $this->readShow($DisplayParams, true, 'canread');
    }

    public function canBook(): bool
    {
        $Permissions = new Permissions($this->Users, $this->entityData);
        $can = json_decode($this->entityData['canbook'], true, 512, JSON_THROW_ON_ERROR);
        return $Permissions->getCan($can);
    }

    public function canBookInPast(): bool
    {
        return $this->Users->isAdmin || (bool) $this->entityData['book_users_can_in_past'];
    }

    public function duplicate(): int
    {
        $this->canOrExplode('read');

        // handle the blank_value_on_duplicate attribute on extra fields
        $metadata = (new Metadata($this->entityData['metadata']))->blankExtraFieldsValueOnDuplicate();
        // figure out the custom id
        $customId = $this->getNextCustomId((int) $this->entityData['category']);

        $sql = 'INSERT INTO items(team, title, date, body, userid, canread, canwrite, canbook, category, elabid, metadata, custom_id, content_type)
            VALUES(:team, :title, CURDATE(), :body, :userid, :canread, :canwrite, :canbook, :category, :elabid, :metadata, :custom_id, :content_type)';
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $this->entityData['title'],
            'body' => $this->entityData['body'],
            'userid' => $this->Users->userData['userid'],
            'elabid' => Tools::generateElabid(),
            'canread' => $this->entityData['canread'],
            'canwrite' => $this->entityData['canwrite'],
            'canbook' => $this->entityData['canbook'],
            'category' => $this->entityData['category'],
            'metadata' => $metadata,
            'custom_id' => $customId,
            'content_type' => $this->entityData['content_type'],
        ));
        $newId = $this->Db->lastInsertId();

        if ($this->id === null) {
            throw new IllegalActionException('Try to duplicate without an id.');
        }
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);
        // also add a link to the previous resource
        $ItemsLinks = new ItemsLinks(new self($this->Users, $newId));
        $ItemsLinks->setId($this->id);
        $ItemsLinks->postAction(Action::Create, array());

        return $newId;
    }

    public function destroy(): bool
    {
        parent::destroy();

        // delete links of this item in experiments with this item linked
        $sql = 'DELETE FROM experiments_links WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        // same for items_links
        $sql = 'DELETE FROM items_links WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // delete from pinned
        return $this->Pins->cleanup();
    }

    protected function getNextCustomId(int $category): ?int
    {
        $sql = 'SELECT custom_id FROM items WHERE custom_id IS NOT NULL AND team = :team AND category = :category ORDER BY custom_id DESC LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if ($res === false || $res['custom_id'] === null) {
            return null;
        }
        return ++$res['custom_id'];
    }
}
