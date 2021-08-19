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
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SetIdTrait;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the todolist
 */
class Todolist implements CrudInterface
{
    use SetIdTrait;

    use SortableTrait;

    protected Db $Db;

    public function __construct(private int $userid, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function create(ContentParamsInterface $params): int
    {
        $sql = 'INSERT INTO todolist(body, userid) VALUES(:content, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Select all the todoitems for a user
     */
    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT id, body, creation_time FROM todolist WHERE userid = :userid ORDER BY ordering ASC, creation_time DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetchAll($req);
    }

    public function update(ContentParamsInterface $params): bool
    {
        $sql = 'UPDATE todolist SET body = :content WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM todolist WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Clear all todoitems from the todolist
     */
    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM todolist WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }
}
