<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models\Notifications;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Users;
use Elabftw\Traits\SetIdTrait;

use function json_decode;
use PDO;

/**
 * Notifications for a user
 */
class UserNotifications implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    private int $userid;

    public function __construct(private Users $users, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->userid = $this->users->userData['userid'];
        $this->setId($id);
    }

    public function readAll(): array
    {
        // for step deadline only select notifications where deadline is in the next hour
        $sql = 'SELECT id, category, body, is_ack, created_at, userid FROM notifications WHERE userid = :userid AND (
                (category != :deadline AND category NOT IN (20, 30)) OR
                (category = :deadline and JSON_UNQUOTE(JSON_EXTRACT(body, :deadline_json_path)) > (NOW() - INTERVAL 1 HOUR))
            ) ORDER BY created_at DESC LIMIT 10';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':deadline', Notifications::StepDeadline->value, PDO::PARAM_INT);
        $req->bindValue(':deadline_json_path', '$.deadline', PDO::PARAM_STR);
        $this->Db->execute($req);

        $notifs = $req->fetchAll();
        foreach ($notifs as $key => &$notif) {
            $notif['body'] = json_decode($notif['body'], true, 512, JSON_THROW_ON_ERROR);
            // remove the step deadline web notif if user doesn't want it shown
            if ($this->users->userData['notif_step_deadline'] === 0 && ((int) $notif['category']) === Notifications::StepDeadline->value) {
                unset($notifs[$key]);
            }
        }
        return $notifs;
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM notifications WHERE userid = :userid AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return 1;
    }

    public function patch(Action $action, array $params): array
    {
        // currently the only update action is to ack it, so no need to check for anything else
        // permission is checked with the userid AND
        $sql = 'UPDATE notifications SET is_ack = 1 WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }

    public function getPage(): string
    {
        return sprintf('users/%d/notifications/', $this->userid);
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
