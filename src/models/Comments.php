<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SetIdTrait;
use function nl2br;
use PDO;

/**
 * All about the comments
 */
class Comments implements CrudInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function create(ContentParamsInterface $params): int
    {
        $sql = 'INSERT INTO ' . $this->Entity->type . '_comments(datetime, item_id, comment, userid)
            VALUES(:datetime, :item_id, :content, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':datetime', date('Y-m-d H:i:s'));
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':content', nl2br($params->getContent()));
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);
        if ($this->Entity instanceof Experiments) {
            $this->createNotification();
        }

        return $this->Db->lastInsertId();
    }

    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT ' . $this->Entity->type . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->Entity->type . '_comments
            LEFT JOIN users ON (' . $this->Entity->type . '_comments.userid = users.userid)
            WHERE item_id = :id ORDER BY ' . $this->Entity->type . '_comments.datetime ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('read');
        $sql = 'UPDATE ' . $this->Entity->type . '_comments SET
            comment = :content
            WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', nl2br($params->getContent()), PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_comments WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Create a notification to the experiment owner to alert a comment was posted
     * (issue #160). Only for an experiment we don't own.
     */
    private function createNotification(): void
    {
        $this->Entity->populate();
        if ($this->Entity->entityData['userid'] === $this->Entity->Users->userData['userid']) {
            return;
        }

        $body = array(
            'experiment_id' => $this->Entity->id,
            'commenter_userid' => (int) $this->Entity->Users->userData['userid'],
        );

        // create notifications object with the userid of the owner
        $Notifications = new Notifications(new Users((int) $this->Entity->entityData['userid']));
        $Notifications->create(new CreateNotificationParams(Notifications::COMMENT_CREATED, $body));
    }
}
