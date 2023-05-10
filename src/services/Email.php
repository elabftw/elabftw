<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as Memail;
use Symfony\Component\Mime\RawMessage;

/**
 * Email service
 */
class Email
{
    public string $footer;

    private Address $from;

    /** general toggle to disable sending if the email is not configured */
    private bool $isEnabled = true;

    public function __construct(private MailerInterface $Mailer, private LoggerInterface $Log, string $mailFrom)
    {
        $this->footer = $this->makeFooter();
        if ($mailFrom === 'notconfigured@example.com') {
            // we don't want to throw an exception here, just fail but log an error
            $this->Log->warning('', array('Warning' => 'Sending emails is not configured!'));
            $this->isEnabled = false;
        }
        $this->from = new Address($mailFrom, 'eLabFTW');
    }

    /**
     * Send an email
     */
    public function send(RawMessage $email): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        try {
            $this->Mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // for email error, don't display error to user as it might contain sensitive information
            // but log it and display general error. See #841
            $this->Log->error('', array('exception' => $e));
            throw new ImproperActionException('Could not send email! Full error message has been logged.');
        }
        return true;
    }

    /**
     * Send a test email
     */
    public function testemailSend(string $email): bool
    {
        $message = (new Memail())
        ->subject('[eLabFTW] ' . _('Test email'))
        ->from($this->from)
        ->to(new Address($email, 'Admin eLabFTW'))
        ->text('Congratulations, you correctly configured eLabFTW to send emails! :)' . $this->footer);

        return $this->send($message);
    }

    /**
     * Send a mass email to all users
     */
    public function massEmail(string $targetType, ?int $targetId, string $subject, string $body): int
    {
        if (empty($subject)) {
            $subject = '[eLabFTW] No subject';
        }

        // set from
        $from = $this->from;

        // get all email addresses
        $emails = $this->getAllEmails($targetType, $targetId);

        $message = (new Memail())
        ->subject($subject)
        ->from($from)
        ->to($from)
        // Set recipients in BCC to protect email addresses
        ->bcc(...$emails)
        ->text($body . $this->footer);

        $this->send($message);
        return count($emails);
    }

    public function sendEmail(Address $to, string $subject, string $body): bool
    {
        $message = (new Memail())
        ->subject($subject)
        ->from($this->from)
        ->to($to)
        ->text($body . $this->footer);

        return $this->send($message);
    }

    public function notifySysadminsTsBalance(int $tsBalance): bool
    {
        $emails = $this->getSysadminEmails();
        $subject = '[eLabFTW] Warning: timestamp balance low!';
        $body = sprintf('Warning: the number of timestamps left is low! %d timestamps left.', $tsBalance);
        $message = (new Memail())
            ->subject($subject)
            ->from($this->from)
            ->to(...$emails)
            ->text($body . $this->footer);
        return $this->send($message);
    }

    private function getSysadminEmails(): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT email, CONCAT(firstname, " ", lastname) AS fullname FROM users WHERE validated = 1 AND archived = 0 AND is_sysadmin = 1';
        $req = $Db->prepare($sql);
        $Db->execute($req);
        $emails = array();
        foreach ($req->fetchAll() as $user) {
            $emails[] = new Address($user['email'], $user['fullname']);
        }
        return $emails;
    }

    /**
     * Get email for all active users on instance, in team or teamgroup
     */
    private function getAllEmails(string $targetType, ?int $targetId): array
    {
        $select = 'SELECT email, CONCAT(firstname, " ", lastname) AS fullname FROM users';
        switch($targetType) {
            case 'team':
                $join = 'CROSS JOIN users2teams ON (users2teams.users_id = users.userid)';
                $filter = 'AND users2teams.teams_id = :id';
                break;
            case 'teamgroup':
                $join = 'CROSS JOIN users2team_groups ON (users2team_groups.userid = users.userid)';
                $filter = 'AND users2team_groups.groupid = :id';
                break;
            default:
                $join = '';
                $filter = '';
        }
        $where = 'WHERE users.validated = 1 AND users.archived = 0';
        $sql = sprintf('%s %s %s %s', $select, $join, $where, $filter);
        $Db = Db::getConnection();
        $req = $Db->prepare($sql);
        if (str_starts_with($targetType, 'team')) {
            $req->bindParam(':id', $targetId, PDO::PARAM_INT);
        }
        $Db->execute($req);

        $emails = array();
        foreach ($req->fetchAll() as $user) {
            $emails[] = new Address($user['email'], $user['fullname']);
        }
        return $emails;
    }

    private function makeFooter(): string
    {
        return sprintf("\n\n~~~\n%s %s\n", _('Sent from eLabFTW'), Config::fromEnv('SITE_URL'));
    }
}
