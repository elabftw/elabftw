<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use function array_column;
use function count;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use PDO;
use function rtrim;
use const SECRET_KEY;
use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Symfony\Component\HttpFoundation\Request;

/**
 * Email service
 */
class Email
{
    public string $footer;

    public function __construct(private Config $Config, private Users $Users)
    {
        $this->footer = $this->makeFooter();
    }

    /**
     * Send an email
     *
     * @throws ImproperActionException
     * @return int number of email sent
     */
    public function send(Swift_Message $message): int
    {
        $mailer = $this->getMailer();
        $res = $mailer->send($message);
        if ($res === 0) {
            throw new ImproperActionException('Could not send email!');
        }
        return $res;
    }

    /**
     * Send a test email
     *
     * @throws ImproperActionException
     */
    public function testemailSend(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ImproperActionException('Bad email!');
        }

        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject(_('[eLabFTW] Test email'))
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => 'Admin eLabFTW'))
        // Give it a body
        ->setBody('Congratulations, you correctly configured eLabFTW to send emails! :)' . $this->footer);

        return (bool) $this->send($message);
    }

    /**
     * Send a mass email to all users
     *
     * @return int number of emails sent
     */
    public function massEmail(string $subject, string $body, bool $teamFilter = false): int
    {
        if (empty($subject)) {
            $subject = 'No subject';
        }

        // set from
        if ($teamFilter) {
            $from = array($this->Users->userData['email'] => $this->Users->userData['fullname']);
        } else {
            $from = array($this->Config->configArr['mail_from'] => 'eLabFTW');
        }

        // get all email addresses
        $emails = $this->getAllEmails($teamFilter);

        $message = (new Swift_Message())
        ->setSubject($subject)
        ->setFrom($from)
        ->setTo($from)
        // Set recipients in BCC to protect email addresses
        ->setBcc($emails)
        ->setBody($body . $this->footer);

        return $this->send($message);
    }

    /**
     * Send an email to the admin of a team
     *
     * @param array<string, mixed> $userInfo to get the email and name of new user
     */
    public function alertAdmin(int $team, array $userInfo, bool $needValidation = true): void
    {
        if ($this->Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return;
        }
        // now let's get the URL so we can have a nice link in the email
        $Request = Request::createFromGlobals();
        $url = rtrim(Tools::getUrl($Request), '/') . '/admin.php';

        // Create the message
        $main = sprintf(
            _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
            $userInfo['name'],
            $userInfo['email'],
        );
        if ($needValidation) {
            $main .= ' ' . sprintf(
                _('Head to the admin panel to validate the account: %s'),
                $url,
            );
        }

        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject(_('[eLabFTW] New user registered'))
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To
        ->setTo($this->getAdminEmail($team))
        // Give it a body
        ->setBody($main . $this->footer);
        // SEND EMAIL
        $this->send($message);
    }

    /**
     * Send an email to a new user to notify that admin validation is required.
     * This exists because experience shows that users don't read the notification and expect
     * their account to work right away.
     *
     * @param string $email email of the user to notify
     */
    public function alertUserNeedValidation($email): void
    {
        if ($this->Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return;
        }
        // Create the message
        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject(_('[eLabFTW] Your account has been created'))
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To
        ->setTo($email)
        // Give it a body
        ->setBody(_('Hi. Your account has been created but it is currently inactive (you cannot log in). The team admin has been notified and will validate your account. You will receive an email when it is done.') . $this->footer);
        // SEND EMAIL
        $this->send($message);
    }

    /**
     * Alert a user that they are validated
     *
     * @param string $email email of the newly validated user
     */
    public function alertUserIsValidated($email): void
    {
        if ($this->Config->configArr['mail_from'] === 'notconfigured@example.com') {
            return;
        }

        // now let's get the URL so we can have a nice link in the email
        $Request = Request::createFromGlobals();
        $url = rtrim(Tools::getUrl($Request), '/') . '/login.php';

        // Create the message
        $message = (new Swift_Message())
        // Give the message a subject
        // no i18n here
        ->setSubject('[eLabFTW] Account validated')
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => 'eLabFTW'))
        // Give it a body
        ->setBody(_('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login: ') . $url . $this->footer);
        // now we try to send the email
        $this->send($message);
    }

    /**
     * Get email for all active users
     */
    private function getAllEmails(bool $fromTeam = false): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT email, teams_id FROM users CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE validated = 1 AND archived = 0';
        if ($fromTeam) {
            $sql .= ' AND users2teams.teams_id = :team';
        }
        $req = $Db->prepare($sql);
        if ($fromTeam) {
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        }
        $Db->execute($req);

        return array_column($Db->fetchAll($req), 'email');
    }

    private function makeFooter(): string
    {
        $url = Tools::getUrl(Request::createFromGlobals());
        return sprintf("\n\n~~~\n%s %s\n", _('Sent from eLabFTW'), $url);
    }

    /**
     * Fetch the email(s) of the admin(s) for a team
     *
     * @param int $team
     *
     * @return scalar[]
     */
    private function getAdminEmail($team): array
    {
        // array for storing email addresses of admin(s)
        $arr = array();
        $Db = Db::getConnection();

        $sql = 'SELECT email FROM users
             CROSS JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = :team)
             WHERE (`usergroup` = 1 OR `usergroup` = 2 OR `usergroup` = 3)';
        $req = $Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->execute();

        while ($email = $req->fetchColumn()) {
            $arr[] = (string) $email;
        }

        // if we have only one admin, we need to have an associative array
        if (count($arr) === 1) {
            return array($arr[0] => 'Admin eLabFTW');
        }

        return $arr;
    }

    /**
     * Return Swift_Mailer instance and choose between sendmail and smtp
     */
    private function getMailer(): Swift_Mailer
    {

        // Choose mail transport method; either smtp or sendmail
        if ($this->Config->configArr['mail_method'] === 'smtp') {
            if ($this->Config->configArr['smtp_encryption'] === 'none') {
                $transport = new Swift_SmtpTransport(
                    $this->Config->configArr['smtp_address'],
                    $this->Config->configArr['smtp_port']
                );
            } else {
                $transport = new Swift_SmtpTransport(
                    $this->Config->configArr['smtp_address'],
                    $this->Config->configArr['smtp_port'],
                    $this->Config->configArr['smtp_encryption']
                );
            }

            if ($this->Config->configArr['smtp_password']) {
                $transport->setUsername($this->Config->configArr['smtp_username'])
                ->setPassword(Crypto::decrypt(
                    $this->Config->configArr['smtp_password'],
                    Key::loadFromAsciiSafeString(SECRET_KEY)
                ));
            }
        } else {
            // Use locally installed MTA (aka sendmail); Default
            $transport = new Swift_SendmailTransport($this->Config->configArr['sendmail_path'] . ' -bs');
        }

        return new Swift_Mailer($transport);
    }
}
