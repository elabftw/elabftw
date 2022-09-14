<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use function json_decode;
use PDO;

/**
 * Notification system
 */
class Notifications implements RestInterface
{
    use SetIdTrait;

    public const COMMENT_CREATED = 10;

    public const USER_CREATED = 11;

    public const USER_NEED_VALIDATION = 12;

    // when a step has a deadline with notifications activated
    public const STEP_DEADLINE = 13;

    // when an event is deleted in the team scheduler
    public const EVENT_DELETED = 14;

    /**
     * Send an email to a new user to notify that admin validation is required.
     * This exists because experience shows that users don't read the notification and expect
     * their account to work right away.
     */
    public const SELF_NEED_VALIDATION = 20;

    // when our account has been validated
    public const SELF_IS_VALIDATED = 30;

    // when there was an error during pdf generation because of MathJax
    public const MATHJAX_FAILED = 40;

    // when an attached PDF file cannot be appended during PDF export
    public const PDF_APPENDMENT_FAILED = 50;

    // when there is a problem with the PDF creation
    public const PDF_GENERIC_ERROR = 60;

    protected Db $Db;

    private int $userid;

    public function __construct(private Users $users, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->userid = $this->users->userData['userid'];
        $this->setId($id);
    }

    public function create(CreateNotificationParams $params): int
    {
        $category = $params->getCategory();

        $sendEmail = $this->getPref($category, '_email');
        $webNotif = $this->getPref($category);

        $isAck = 1;
        if ($webNotif === 1) {
            $isAck = 0;
        }

        $sql = 'INSERT INTO notifications(userid, category, send_email, body, is_ack) VALUES(:userid, :category, :send_email, :body, :is_ack)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindParam(':send_email', $sendEmail, PDO::PARAM_INT);
        $req->bindValue(':body', $params->getContent(), PDO::PARAM_STR);
        $req->bindParam(':is_ack', $isAck, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function createIfNotExists(CreateNotificationParams $params): int
    {
        $body = json_decode($params->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // check if a similar notification is not already there
        $sql = 'SELECT id FROM notifications WHERE category = :category AND JSON_EXTRACT(body, "$.step_id") = :step_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':category', $params->getCategory(), PDO::PARAM_INT);
        $req->bindValue(':step_id', $body['step_id'], PDO::PARAM_INT);
        $this->Db->execute($req);
        // if there is a notification for this step id, delete it
        if ($req->rowCount() > 0) {
            $sql = 'DELETE FROM notifications WHERE id = :id';
            $reqDel = $this->Db->prepare($sql);
            $reqDel->bindValue(':id', $req->fetch()['id'], PDO::PARAM_INT);
            $reqDel->execute();
            return 0;
        }
        // otherwise, create a notification for it
        return $this->create($params);
    }

    public function createMultiUsers(CreateNotificationParams $params, array $useridArr, int $currentUserid): int
    {
        foreach ($useridArr as $userid) {
            $userid = (int) $userid;
            // don't self notify this action
            if ($userid === $currentUserid) {
                continue;
            }
            $this->userid = $userid;
            $this->create($params);
        }
        return count($useridArr);
    }

    public function readAll(): array
    {
        // for step deadline only select notifications where deadline is in the next hour
        $sql = 'SELECT id, category, body, is_ack, created_at, userid FROM notifications WHERE userid = :userid AND (
                category != :deadline OR
                (category = :deadline and JSON_UNQUOTE(JSON_EXTRACT(body, :deadline_json_path)) > (NOW() - INTERVAL 1 HOUR))
            ) ORDER BY created_at DESC LIMIT 10';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':deadline', self::STEP_DEADLINE, PDO::PARAM_INT);
        $req->bindValue(':deadline_json_path', '$.deadline', PDO::PARAM_STR);
        $this->Db->execute($req);

        $notifs = $req->fetchAll();
        foreach ($notifs as $key => &$notif) {
            $notif['body'] = json_decode($notif['body'], true, 512, JSON_THROW_ON_ERROR);
            // remove the step deadline web notif if user doesn't want it shown
            if ($this->users->userData['notif_step_deadline'] === '0' && ((int) $notif['category']) === self::STEP_DEADLINE) {
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

    private function getPref(int $category, string $suffix = ''): int
    {
        // only categories inferior to 20 have a user setting for email/web notif
        if ($category >= 20) {
            return 1;
        }

        $pref = match ($category) {
            self::COMMENT_CREATED => 'notif_comment_created',
            self::USER_CREATED => 'notif_user_created',
            self::USER_NEED_VALIDATION => 'notif_user_need_validation',
            self::STEP_DEADLINE => 'notif_step_deadline',
            self::EVENT_DELETED => 'notif_event_deleted',
            default => throw new ImproperActionException('Invalid category for preferences.'),
        };

        // use a new Users() here because userid might have changed (see multiuser create)
        return (new Users($this->userid))->userData[$pref . $suffix];
    }
}
