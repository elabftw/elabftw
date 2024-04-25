<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\AuditEvent\OnboardingEmailSent;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Notifications;
use Elabftw\Factories\NotificationsFactory;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Users;
use PDO;
use Symfony\Component\Mime\Address;

use function bindtextdomain;
use function count;
use function dirname;
use function putenv;
use function setlocale;
use function textdomain;

/**
 * Email notification system
 */
class EmailNotifications
{
    protected const BASE_SUBJECT = '[eLabFTW] ';

    protected Db $Db;

    public function __construct(protected Email $emailService)
    {
        $this->Db = Db::getConnection();
    }

    public function sendEmails(): int
    {
        $toSend = $this->getNotificationsToSend();
        foreach ($toSend as $notif) {
            $targetUser = new Users((int) $notif['userid']);
            $this->setLang($targetUser->userData['lang']);
            $to = new Address($targetUser->userData['email'], $targetUser->userData['fullname']);
            $Factory = new NotificationsFactory((int) $notif['category'], $notif['body']);
            $email = $Factory->getMailable()->getEmail();
            $cc = array_key_exists('cc', $email) ? $email['cc'] : null;
            $htmlBody = array_key_exists('htmlBody', $email) ? (string) $email['htmlBody'] : null;
            $isEmailSent = $this->emailService->sendEmail(
                $to,
                self::BASE_SUBJECT . $email['subject'],
                $email['body'],
                $cc,
                $htmlBody,
            );
            if ($isEmailSent) {
                $this->setEmailSent((int) $notif['id']);
                if (Notifications::tryFrom($notif['category']) === Notifications::OnboardingEmail) {
                    AuditLogs::create(new OnboardingEmailSent($email['team'], (int) $notif['userid'], $email['forAdmin']));
                }
            }
        }
        return count($toSend);
    }

    /**
     * set the lang to the one of the target user (see issue #2700)
     * @psalm-suppress UnusedFunctionCall
     */
    protected function setLang(string $lang = 'en_GB'): void
    {
        $locale = $lang . '.utf8';
        // configure gettext
        $domain = 'messages';
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, dirname(__DIR__) . '/langs');
        textdomain($domain);
    }

    protected function getNotificationsToSend(): array
    {
        // for step deadline only select notifications where deadline is in the next 30 min
        $sql = 'SELECT id, userid, category, body
            FROM notifications
            WHERE send_email = 1
                AND email_sent = 0
                AND (category <> :deadline
                    OR (category = :deadline
                        AND CAST(NOW() AS DATETIME) > CAST(DATE_ADD(CAST(body->"$.deadline" AS DATETIME), INTERVAL - 30 MINUTE) AS DATETIME)
                    )
                )';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':deadline', Notifications::StepDeadline->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    private function setEmailSent(int $id): bool
    {
        $sql = 'UPDATE notifications SET email_sent = 1, email_sent_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
