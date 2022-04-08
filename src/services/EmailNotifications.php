<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function bindtextdomain;
use function count;
use function dirname;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications;
use Elabftw\Models\Users;
use function json_decode;
use PDO;
use function putenv;
use function setlocale;
use const SITE_URL;
use function sprintf;
use Symfony\Component\Mime\Address;
use function textdomain;

/**
 * Email notification system
 */
class EmailNotifications
{
    protected Db $Db;

    public function __construct(private Email $emailService)
    {
        $this->Db = Db::getConnection();
    }

    public function sendEmails(): int
    {
        $toSend = $this->getNotificationsToSend();
        foreach ($toSend as $notif) {
            $targetUser = new Users((int) $notif['userid']);
            $this->setLang((int) $notif['userid']);
            $to = new Address($targetUser->userData['email'], $targetUser->userData['fullname']);
            $email = $this->notif2Email($notif);
            if ($this->emailService->sendEmail($to, $email['subject'], $email['body'])) {
                $this->setEmailSent((int) $notif['id']);
            }
        }
        return count($toSend);
    }

    private function setEmailSent(int $id): bool
    {
        $sql = 'UPDATE notifications SET email_sent = 1, email_sent_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    // set the lang to the one of the target user (see issue #2700)
    private function setLang(int $userid): void
    {
        $targetUser = new Users((int) $userid);
        $locale = $targetUser->userData['lang'] . '.utf8';
        // configure gettext
        $domain = 'messages';
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, dirname(__DIR__) . '/langs');
        textdomain($domain);
    }

    private function getNotificationsToSend(): array
    {
        $sql = 'SELECT id, userid, category, body FROM notifications WHERE send_email = 1 AND email_sent = 0 AND (
            CASE WHEN category = :deadline THEN CAST(NOW() AS DATETIME) > CAST(
                DATE_ADD(
                    CAST(JSON_EXTRACT(body, "$.deadline") AS DATETIME), INTERVAL - 30 MINUTE) AS DATETIME)
            ELSE 1=1 END)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':deadline', Notifications::STEP_DEADLINE, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Transform a notification in an array with subject and body for sending an email
     */
    private function notif2Email(array $notif): array
    {
        $subject = '[eLabFTW] ';
        $notifBody = json_decode($notif['body'], true, 512, JSON_THROW_ON_ERROR);
        switch ((int) $notif['category']) {
            case Notifications::COMMENT_CREATED:
                $subject .= _('New comment posted');
                $commenter = new Users((int) $notifBody['commenter_userid']);
                $url = SITE_URL . '/experiments.php?mode=view&id=' . $notifBody['experiment_id'];

                $body = sprintf(
                    _('Hi. %s left a comment on your experiment. Have a look: %s'),
                    $commenter->userData['fullname'],
                    $url,
                );
                break;
            case Notifications::EVENT_DELETED:
                $info = _('A booked slot was deleted from the scheduler.');
                $subject .= $info;
                $url = SITE_URL . '/team.php?item=' . $notifBody['event']['item'];
                $body = sprintf(_('Hi. %s (%s). See item: %s'), $info, $notifBody['actor'], $url);
                break;
            case Notifications::USER_CREATED:
                $subject .= _('New user added to your team');
                $user = new Users((int) $notifBody['userid']);
                $body = sprintf(
                    _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
                    $user->userData['fullname'],
                    $user->userData['email'],
                );
                break;
            case Notifications::USER_NEED_VALIDATION:
                $subject .= _('[ACTION REQUIRED]') . ' ' . _('New user added to your team');
                $user = new Users((int) $notifBody['userid']);
                $base = sprintf(
                    _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
                    $user->userData['fullname'],
                    $user->userData['email'],
                );
                $url = SITE_URL . '/admin.php';
                $body = $base . ' ' . sprintf(_('Head to the admin panel to validate the account: %s'), $url);
                break;
            case Notifications::SELF_NEED_VALIDATION:
                $subject .= _('Your account has been created');
                $body = _('Hi. Your account has been created but it is currently inactive (you cannot log in). The team admin has been notified and will validate your account. You will receive an email when it is done.');
                break;
            case Notifications::SELF_IS_VALIDATED:
                $subject .= _('Account validated');
                $url = SITE_URL . '/login.php';
                $body = _('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login: ') . $url;
                break;
            case Notifications::STEP_DEADLINE:
                $subject .= _('A step deadline is approaching');
                $url = SITE_URL . '/' . $notifBody['entity_page'] . '.php?mode=view&id=' . $notifBody['entity_id'];
                $body = _('Hello. A step deadline is approaching: ') . $url;
                break;
            default:
                throw new ImproperActionException('Invalid notification category');
        }
        return array('subject' => $subject, 'body' => $body);
    }
}
