<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Models\Users;
use PDO;
use Symfony\Component\Mime\Address;

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
            $body = Transform::emailNotif($notif);
            if ($this->emailService->sendEmail($to, $this->getSubject((int) $notif['category']), $body)) {
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
        $sql = 'SELECT id, userid, category, body FROM notifications WHERE send_email = 1 AND email_sent = 0';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $this->Db->fetchAll($req);
    }
}
