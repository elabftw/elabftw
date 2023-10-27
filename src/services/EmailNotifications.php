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
use Elabftw\Enums\Notifications;
use Elabftw\Factories\NotificationsFactory;
use Elabftw\Models\Users;
use PDO;
use function putenv;
use function setlocale;
use Symfony\Component\Mime\Address;
use function textdomain;

/**
 * Email notification system
 */
class EmailNotifications
{
    private const BASE_SUBJECT = '[eLabFTW] ';

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
            $Factory = new NotificationsFactory((int) $notif['category'], $notif['body']);
            $email = $Factory->getMailable()->getEmail();
            if ($this->emailService->sendEmail($to, self::BASE_SUBJECT . $email['subject'], $email['body'])) {
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

    /**
     * set the lang to the one of the target user (see issue #2700)
     * @psalm-suppress UnusedFunctionCall
     */
    private function setLang(int $userid): void
    {
        $targetUser = new Users($userid);
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
        $sql='SELECT id, userid, category, body FROM notifications
            WHERE send_email = 1 AND email_sent = 0 AND (category <> :deadline OR (category = :deadline AND CAST(NOW() AS DATETIME) > CAST(DATE_ADD(CAST(JSON_EXTRACT(body, "$.deadline") AS DATETIME), INTERVAL - 30 MINUTE) AS DATETIME)))';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':deadline', Notifications::StepDeadline->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }
}
