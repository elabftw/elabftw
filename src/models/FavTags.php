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

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\TagParam;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * The favorite tags of a user
 */
class FavTags implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(private Users $Users, ?int $id = null)
    {
        $this->setId($id);
        $this->Db = Db::getConnection();
    }

    public function getPage(): string
    {
        return 'api/v2/favtags/';
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(new TagParam($reqBody['tag']));
    }

    public function patch(Action $action, array $params): array
    {
        return array();
    }

    public function readAll(): array
    {
        $sql = 'SELECT users_id, tags_id, tag FROM favtags2users
           LEFT JOIN tags ON (tags.id = favtags2users.tags_id) WHERE users_id = :userid
           ORDER BY tags.tag';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM favtags2users WHERE users_id = :userid AND tags_id = :tagId';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tagId', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function create(TagParam $params): int
    {
        // get the tag id
        $sql = 'SELECT id FROM tags WHERE team = :team AND tag = :tag';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':tag', $params->getContent());
        $this->Db->execute($req);
        $tagId = (int) $req->fetchColumn();

        // if no tag is found, throw an error
        if ($tagId === 0) {
            throw new ImproperActionException(_('Could not find tag. Please enter an existing tag.'));
        }

        if ($this->isFavorite($tagId)) {
            return 0;
        }

        // now add it as favorite
        $sql = 'INSERT INTO favtags2users (users_id, tags_id) VALUES (:userid, :tagId)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tagId', $tagId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        return (int) $this->Db->execute($req);
    }

    // check if a tag is not already favorite for the user
    private function isFavorite(int $tagId): bool
    {
        $sql = 'SELECT * FROM favtags2users WHERE tags_id = :tagId AND users_id = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tagId', $tagId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->rowCount() > 0;
    }
}
