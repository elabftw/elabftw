<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CreateNotificationParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * Notification system
 */
class Notifications implements CrudInterface
{
    protected Db $Db;

    public function __construct(private int $userid)
    {
        $this->Db = Db::getConnection();
    }

    public function create(CreateNotificationParamsInterface $params): int
    {
        // TODO send_email will be in function of user preference depending on category of notif
        $sendEmail = 1;

        $sql = 'INSERT INTO notifications(userid, category, send_email, body) VALUES(:userid, :category, :send_email, :body)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':category', $params->getCategory(), PDO::PARAM_INT);
        $req->bindParam(':send_email', $sendEmail, PDO::PARAM_INT);
        $req->bindValue(':body', $params->getContent(), PDO::PARAM_STR);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT id, category, body, is_ack FROM notifications WHERE userid = :userid ORDER BY created_at DESC LIMIT 10';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $notifs = $this->Db->fetchAll($req);
        foreach ($notifs as &$notif) {
            $notif['body'] = json_decode($notif['body'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $notifs;
    }

    public function update(ContentParamsInterface $params): bool
    {
        return true;
    }

    /**
     * Delete all notifications for that user
     */
    public function destroy(): bool
    {
        $sql = 'DELETE FROM notifications WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
