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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Monolog\Logger;
use PDO;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as Memail;
use Symfony\Component\Mime\RawMessage;
use function urlencode;

/**
 * Email service
 */
class Email
{
    public string $footer;

    private Address $from;

    public function __construct(private Config $Config, private Logger $Log)
    {
        $this->footer = $this->makeFooter();
        $this->from = new Address($Config->configArr['mail_from'], 'eLabFTW');
    }

    /**
     * Send an email
     */
    public function send(RawMessage $email): bool
    {
        if ($this->Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return false;
        }
        $mailer = $this->getMailer();
        try {
            $mailer->send($email);
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
    public function massEmail(string $subject, string $body, ?int $team = null): int
    {
        if (empty($subject)) {
            $subject = '[eLabFTW] No subject';
        }

        // set from
        $from = $this->from;

        // get all email addresses
        $emails = $this->getAllEmails($team);

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

    public function notifySysadminsTsBalance(int $tsBalance): void
    {
        $emails = $this->getSysadminEmails();
        $subject = '[eLabFTW] Warning: timestamp balance low!';
        $body = sprintf('Warning: the number of timestamps left is low! %d timestamps left.', $tsBalance);
        $message = (new Memail())
            ->subject($subject)
            ->from($this->from)
            ->to(...$emails)
            ->text($body . $this->footer);
        $this->send($message);
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
     * Get email for all active users
     */
    private function getAllEmails(?int $team): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT email, teams_id, CONCAT(firstname, " ", lastname) AS fullname  FROM users CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE validated = 1 AND archived = 0';
        if ($team !== null) {
            $sql .= ' AND users2teams.teams_id = :team';
        }
        $req = $Db->prepare($sql);
        if ($team !== null) {
            $req->bindParam(':team', $team, PDO::PARAM_INT);
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

    /**
     * Return Mailer instance
     */
    private function getMailer(): MailerInterface
    {
        $username = '';
        $password = '';
        if ($this->Config->configArr['smtp_password']) {
            $username = $this->Config->configArr['smtp_username'];
            $password = Crypto::decrypt(
                $this->Config->configArr['smtp_password'],
                Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'))
            );
        }

        $dsn = sprintf(
            'smtp://%s:%s@%s:%d',
            $username,
            urlencode($password),
            $this->Config->configArr['smtp_address'],
            $this->Config->configArr['smtp_port'],
        );

        $dsn .= '?verify_peer=' . $this->Config->configArr['smtp_verify_cert'];

        return new Mailer(Transport::fromDsn($dsn));
    }
}
