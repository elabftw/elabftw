<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Services\Email;
use PDO;
use Symfony\Component\Mime\Address;

/**
 * Notification system
 */
class Notifications
{
    protected Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    public function create(int $userid, int $category, string $body): int
    {
        // TODO send_email will be in function of user preference depending on category of notif
        $sql = 'INSERT INTO notifications(userid, category, send_email, body) VALUES(:userid, 1, :category, :body)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindParam(':body', $body, PDO::PARAM_STR);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function createNewComment(int $userid, string $commenter, string $url): void
    {
        $this->setLang($userid);

        $body = sprintf(
            _('Hi. %s left a comment on your experiment. Have a look: %s'),
            $commenter,
            $url,
        );
        $this->create($userid, 1, $body);
    }

    public function sendEmails(Email $emailService): int
    {
        $toSend = $this->getNotificationsToSend();

        foreach ($toSend as $notif) {
            $targetUser = new Users((int) $notif['userid']);
            $this->setLang((int) $notif['userid']);
            $to = new Address($targetUser->userData['email'], $targetUser->userData['fullname']);
            if ($emailService->sendEmail($to, $this->getSubject((int) $notif['category']), $notif['body'])) {
                $this->setEmailSent($notif['id']);
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

    private function getSubject(int $category): string
    {
        $subject = '[eLabFTW] ';
        if ($category === 1) {
            $subject .= _('New comment posted');
        }
        return $subject;
    }

    private function getNotificationsToSend(): array
    {
        $sql = 'SELECT id, userid, category, body FROM notifications WHERE email_sent = 0';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $this->Db->fetchAll($req);
    }
}
