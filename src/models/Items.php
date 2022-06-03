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
            VALUES(:team, :title, CURDATE(), :body, :userid, :category, :elabid, :canread, :canwrite, :metadata)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':title', _('Untitled'), PDO::PARAM_STR);
        $req->bindParam(':body', $itemTemplate['body'], PDO::PARAM_STR);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindValue(':elabid', $this->generateElabid(), PDO::PARAM_STR);
        $req->bindParam(':canread', $itemTemplate['canread'], PDO::PARAM_STR);
        $req->bindParam(':canwrite', $itemTemplate['canwrite'], PDO::PARAM_STR);
        $req->bindParam(':metadata', $itemTemplate['metadata'], PDO::PARAM_STR);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($params->getTags(), $newId);
        $this->Links->duplicate((int) $itemTemplate['id'], $newId, true);
        $this->Steps->duplicate((int) $itemTemplate['id'], $newId, true);

        return $newId;
    }

    public function duplicate(): int
    {
        $this->canOrExplode('read');

        $sql = 'INSERT INTO items(team, title, date, body, userid, canread, canwrite, category, elabid, metadata)
            VALUES(:team, :title, CURDATE(), :body, :userid, :canread, :canwrite, :category, :elabid, :metadata)';
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $this->entityData['title'],
            'body' => $this->entityData['body'],
            'userid' => $this->Users->userData['userid'],
            'elabid' => $this->generateElabid(),
            'canread' => $this->entityData['canread'],
            'canwrite' => $this->entityData['canwrite'],
            'category' => $this->entityData['category_id'],
            'metadata' => $this->entityData['metadata'],
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

        // check if we can actually delete items (for non-admins)
        $Team = new Team($this->Users->team);
        if ($Team->getDeletableItem() === 0 && $this->Users->userData['is_admin'] === '0') {
            throw new ImproperActionException(_('Users cannot delete items.'));
        }

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
}
