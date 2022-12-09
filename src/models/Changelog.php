<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
use PDO;

/**
 * All about changelog tables
 */
class Changelog
{
    private Db $Db;

    public function __construct(private AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
    }

    public function create(ContentParamsInterface $params): bool
    {
        // edge case when creating team with non existing user during populate action for dev
        if (empty($this->entity->Users->userData['userid'])) {
            return true;
        }
        // we don't store the full body, let the revisions system handle that
        $content = $params->getContent();
        if ($params->getTarget() === 'body') {
            $content = 'Body was changed, but diff is not recorded because it is not implemented!';
        }
        $sql = 'INSERT INTO ' . $this->entity->type . '_changelog (entity_id, users_id, target, content) VALUES (:entity_id, :users_id, :target, :content)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindParam(':users_id', $this->entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':target', $params->getTarget(), PDO::PARAM_STR);
        $req->bindParam(':content', $content, PDO::PARAM_STR);
        return $this->Db->execute($req);
    }

    public function readAll(): array
    {
        $sql = "SELECT created_at, target, content, CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->entity->type . '_changelog LEFT JOIN users ON (users.userid = ' . $this->entity->type . '_changelog.users_id)
            WHERE entity_id = :entity_id ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
