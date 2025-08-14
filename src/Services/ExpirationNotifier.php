<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Env;
use Elabftw\Models\Users\Users;
use PDO;
use Symfony\Component\Mime\Address;
use Override;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Warn users and their Admins about account expiration
 * Note: this class structure isn't great, and full of nested foreach. While it is possible to do everything in one nice query (probably), it's difficult and error prone, so we adopt the pragmatic approach of doing inefficient code because this code runs from CLI once a week so we don't really care if it takes long
 */
final class ExpirationNotifier extends EmailNotifications
{
    /** @var int NOTIF_PERIOD number of days before the validity date expiration when we start warning users */
    protected const NOTIF_PERIOD = 30;

    #[Override]
    public function sendEmails(OutputInterface $output): int
    {
        return $this->sendAdminsEmails($this->sendUsersEmails());
    }

    protected function sendUsersEmails(): array
    {
        // this will hold the results organized by teams
        $targets = array();
        $emailSubject = _('Account expiration warning');
        $userids = $this->getExpiringUserids();
        foreach ($userids as $userid) {
            $targetUser = new Users($userid);
            $this->setLang($targetUser->userData['lang']);
            $emailBody = sprintf(_('Your account on %s is due to expire on %s and become inaccessible (archived) passed this date. All your data will still be visible to others, but you will not be able to access it through this account.'), Env::asUrl('SITE_URL'), $targetUser->userData['valid_until']);
            $to = new Address($targetUser->userData['email'], $targetUser->userData['fullname']);
            $this->emailService->sendEmail($to, self::BASE_SUBJECT . $emailSubject, $emailBody);
            $UsersHelper = new UsersHelper($userid);
            $teams = $UsersHelper->getTeamsFromUserid();
            // add the user in each team for the admin message
            foreach ($teams as $team) {
                if ($team['is_archived'] === 0) {
                    $targets[$team['id']][] = array(
                        'fullname' => $targetUser->userData['fullname'],
                        'email' => $targetUser->userData['email'],
                        'valid_until' => $targetUser->userData['valid_until'],
                    );
                }
            }

        }
        return $targets;
    }

    protected function sendAdminsEmails(array $targets): int
    {
        $emailSubject = _('Account expiration information');
        $cnt = 0;
        // loop on each team
        foreach ($targets as $team => $users) {
            $TeamsHelper = new TeamsHelper($team);
            $adminsids = $TeamsHelper->getAllAdminsUserid();
            // and for each admin, send an email listing the users that are expiring
            foreach ($adminsids as $adminid) {
                $targetUser = new Users($adminid);
                $this->setLang($targetUser->userData['lang']);
                $emailBody = _('One or several user accounts in your team will expire soon. Their account will become inaccessible (archived). All their data will still be visible to others.') . "\n";
                // display a list of the users that will get archived
                foreach ($users as $user) {
                    $emailBody .= "\n− " . implode(', ', $user);
                }
                $to = new Address($targetUser->userData['email'], $targetUser->userData['fullname']);
                $this->emailService->sendEmail($to, self::BASE_SUBJECT . $emailSubject, $emailBody);
                $cnt += 1;
            }
        }
        return $cnt;
    }

    protected function getExpiringUserids(): array
    {
        $sql = 'SELECT users.userid FROM users WHERE users.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :notif_period DAY)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':notif_period', self::NOTIF_PERIOD);
        $this->Db->execute($req);

        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
