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
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Monolog\Logger;
use PDO;
use function rtrim;
use const SECRET_KEY;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
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

    public function __construct(private Config $Config, private Logger $Log)
    {
        $this->footer = $this->makeFooter();
        $this->from = new Address($Config->configArr['mail_from'], 'eLabFTW');
    }

    /**
     * Send an email
     * TODO make private?
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
        ->subject(_('[eLabFTW] Test email'))
        ->from($this->from)
        ->to(new Address($email, 'Admin eLabFTW'))
        ->text('Congratulations, you correctly configured eLabFTW to send emails! :)' . $this->footer);

        return $this->send($message);
    }

    /**
     * Send a mass email to all users
     */
    public function massEmail(string $subject, string $body, ?int $team = null, ?string $fromEmail = null, ?string $fromName = null): int
    {
        if (empty($subject)) {
            $subject = 'No subject';
        }

        // set from
        $from = $this->from;
        if ($fromEmail !== null && $fromName !== null) {
            $from = new Address($fromEmail, $fromName);
        }

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

    /**
     * Send an email to a new user to notify that admin validation is required.
     * This exists because experience shows that users don't read the notification and expect
     * their account to work right away.
     */
    public function alertUserNeedValidation(string $email): void
    {
        // Create the message
        $message = (new Memail())
        ->subject(_('[eLabFTW] Your account has been created'))
        ->from($this->from)
        ->to(new Address($email))
        ->text(_('Hi. Your account has been created but it is currently inactive (you cannot log in). The team admin has been notified and will validate your account. You will receive an email when it is done.') . $this->footer);
        $this->send($message);
    }

    /**
     * Alert a user that they are validated
     */
    public function alertUserIsValidated(string $email): void
    {
        // now let's get the URL so we can have a nice link in the email
        $Request = Request::createFromGlobals();
        $url = rtrim(Tools::getUrl($Request), '/') . '/login.php';

        $message = (new Memail())
        // no i18n here
        ->subject('[eLabFTW] Account validated')
        ->from($this->from)
        ->to(new Address($email, 'eLabFTW'))
        ->text(_('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login: ') . $url . $this->footer);
        $this->send($message);
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

        $users = $Db->fetchAll($req);
        $emails = array();
        foreach ($users as $user) {
            $emails[] = new Address($user['email'], $user['fullname']);
        }
        return $emails;
    }

    private function makeFooter(): string
    {
        $url = Tools::getUrl(Request::createFromGlobals());
        return sprintf("\n\n~~~\n%s %s\n", _('Sent from eLabFTW'), $url);
    }

    /**
     * Return Swift_Mailer instance and choose between sendmail and smtp
     */
    private function getMailer(): MailerInterface
    {
        // Use locally installed MTA (aka sendmail)
        $dsn = 'sendmail://default';

        // Override default dsn if we use SMTP
        if ($this->Config->configArr['mail_method'] === 'smtp') {
            $username = '';
            $password = '';
            if ($this->Config->configArr['smtp_password']) {
                $username = $this->Config->configArr['smtp_username'];
                $password = Crypto::decrypt(
                    $this->Config->configArr['smtp_password'],
                    Key::loadFromAsciiSafeString(SECRET_KEY)
                );
            }

            $dsn = sprintf(
                'smtp://%s:%s@%s:%d',
                $username,
                $password,
                $this->Config->configArr['smtp_address'],
                $this->Config->configArr['smtp_port'],
            );
        }

        $transport = Transport::fromDsn($dsn);
        return new Mailer($transport);
    }
}
