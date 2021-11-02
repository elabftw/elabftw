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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Maps\Team;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use PDO;

/**
 * All about the database items
 */
class Items extends AbstractEntity
{
    use InsertTagsTrait;

    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->type = 'items';
        $this->page = 'database';
    }

    public function create(EntityParamsInterface $params): int
    {
        $category = (int) $params->getContent();
        $ItemsTypes = new ItemsTypes($this->Users, $category);
        $itemTemplate = $ItemsTypes->read(new ContentParams());

        $sql = 'INSERT INTO items(team, title, date, body, userid, category, elabid, canread, canwrite, metadata)
            VALUES(:team, :title, :date, :body, :userid, :category, :elabid, :canread, :canwrite, :metadata)';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req, array(
            'team' => $this->Users->userData['team'],
            'title' => _('Untitled'),
            'date' => Filter::kdate(),
            'elabid' => $this->generateElabid(),
            'body' => $itemTemplate['body'],
            'userid' => $this->Users->userData['userid'],
            'category' => $category,
            'canread' => $itemTemplate['canread'],
            'canwrite' => $itemTemplate['canwrite'],
            'metadata' => $itemTemplate['metadata'],
        ));

        $newId = $this->Db->lastInsertId();

        $this->insertTags($params->getTags(), $newId);
        $this->Links->duplicate((int) $itemTemplate['id'], $newId, true);
        $this->Steps->duplicate((int) $itemTemplate['id'], $newId, true);

        return $newId;
    }

    public function duplicate(): int
    {
        $this->canOrExplode('read');

        $sql = 'INSERT INTO items(team, title, date, body, userid, canread, canwrite, category, elabid)
            VALUES(:team, :title, :date, :body, :userid, :canread, :canwrite, :category, :elabid)';
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $this->entityData['title'],
            'date' => Filter::kdate(),
            'body' => $this->entityData['body'],
            'userid' => $this->Users->userData['userid'],
            'elabid' => $this->generateElabid(),
            'canread' => $this->entityData['canread'],
            'canwrite' => $this->entityData['canwrite'],
            'category' => $this->entityData['category_id'],
        ));
        $newId = $this->Db->lastInsertId();

        if ($this->id === null) {
            throw new IllegalActionException('Try to duplicate without an id.');
        }
        $this->Links->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);

        return $newId;
    }

    public function destroy(): bool
    {
        $this->canOrExplode('write');

        // check if we can actually delete items (for non-admins)
        $Team = new Team($this->Users->team);
        if ($Team->getDeletableItem() === 0 && $this->Users->userData['is_admin'] === '0') {
            throw new ImproperActionException(_('Users cannot delete items.'));
        }

        // delete the database item
        $sql = 'DELETE FROM items WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->Tags->destroyAll();

        $this->Uploads->destroyAll();

        // delete links of this item in experiments with this item linked
        // get all experiments with that item linked
        $sql = 'SELECT id FROM experiments_links WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        while ($links = $req->fetch()) {
            $delete_sql = 'DELETE FROM experiments_links WHERE id = :links_id';
            $delete_req = $this->Db->prepare($delete_sql);
            $delete_req->bindParam(':links_id', $links['id'], PDO::PARAM_INT);
            $this->Db->execute($delete_req);
        }

        // delete from pinned
        return $this->Pins->cleanup();
    }
}
