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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EmailTarget;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use PDO;
use Psr\Log\LoggerInterface;
use Stevebauman\Hypertext\Transformer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as Memail;
use Symfony\Component\Mime\RawMessage;

use function count;

/**
 * Email service
 */
class Email
{
    public string $footer;

    private Address $from;

    public function __construct(private MailerInterface $Mailer, private LoggerInterface $Log, private string $mailFrom)
    {
        $this->footer = $this->makeFooter();
        $this->from = new Address($mailFrom, 'eLabFTW');
    }

    /**
     * Send an email
     */
    public function send(RawMessage $email): bool
    {
        if ($this->mailFrom === 'notconfigured@example.com') {
            // we don't want to throw an exception here, just fail but log an error
            $this->Log->warning('', array('Warning' => 'Sending emails is not configured!'));
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
    public function massEmail(EmailTarget $target, ?int $targetId, string $subject, string $body, Address $replyTo): int
    {
        if (empty($subject)) {
            $subject = '[eLabFTW] No subject';
        }

        // set from
        $from = $this->from;

        // get all email addresses
        $emails = self::getAllEmailAddresses($target, $targetId);

        $sender = sprintf("\n\nEmail sent by %s. You can reply directly to this email.\n", $replyTo->getName());

        $message = (new Memail())
        ->subject($subject)
        ->from($from)
        ->to($replyTo)
        // Set recipients in BCC to protect email addresses
        ->bcc(...$emails)
        ->replyTo($replyTo)
        ->text($body . $sender . $this->footer);

        $this->send($message);
        return count($emails);
    }

    /**
     * @param null|(\Symfony\Component\Mime\Address|string)[] $cc
     */
    public function sendEmail(
        Address $to,
        string $subject,
        string $body,
        ?array $cc = null,
        ?string $htmlBody = null
    ): bool {
        $message = (new Memail())
        ->subject($subject)
        ->from($this->from)
        ->to($to)
        ->text($body . $this->footer);

        if (!empty($cc)) {
            $message->cc(...$cc);
        }

        if (!empty($htmlBody)) {
            $message->html($htmlBody . $this->footer);

            if (empty($body)) {
                $textWithLinks = (new Transformer())
                    ->keepLinks()
                    ->keepNewLines()
                    ->toText($htmlBody);
                // convert links to plain text and keep the url
                // <a href="url">link text</a> => link text (url)
                $plainText = preg_replace('/<a href="([^"]*)">([^<]*)<\/a>/iu', '$2 ($1)', $textWithLinks);

                $message->text($plainText . $this->footer);
            }
        }

        return $this->send($message);
    }

    public function notifySysadminsTsBalance(int $tsBalance): bool
    {
        $emails = self::getAllEmailAddresses(EmailTarget::Sysadmins);
        $subject = '[eLabFTW] Warning: timestamp balance low!';
        $body = sprintf('Warning: the number of timestamps left is low! %d timestamps left.', $tsBalance);
        $message = (new Memail())
            ->subject($subject)
            ->from($this->from)
            ->to(...$emails)
            ->text($body . $this->footer);
        return $this->send($message);
    }

    /**
     * Get ids of all active users on instance, in team or teamgroup
     * @return int[]
     */
    public static function getIdsOfRecipients(EmailTarget $target, ?int $targetId = null): array
    {
        return array_column(self::getAllEmailAddressesRawData($target, $targetId), 'userid');
    }

    /**
     * Get email addresses of all active users on instance, in team or teamgroup
     * @return Address[]
     */
    public static function getAllEmailAddresses(EmailTarget $target, ?int $targetId = null): array
    {
        $emails = array();
        foreach (self::getAllEmailAddressesRawData($target, $targetId) as $user) {
            $emails[] = new Address($user['email'], $user['fullname']);
        }
        return $emails;
    }

    private function makeFooter(): string
    {
        return sprintf("\n\n~~~\n%s %s\n", _('Sent from eLabFTW'), Config::fromEnv('SITE_URL'));
    }

    private static function getAllEmailAddressesRawData(EmailTarget $target, ?int $targetId = null): array
    {
        $select = 'SELECT DISTINCT users.userid, email, CONCAT(firstname, " ", lastname) AS fullname FROM users';
        switch($target) {
            case EmailTarget::Team:
                $join = 'CROSS JOIN users2teams ON (users2teams.users_id = users.userid)';
                $filter = 'AND users2teams.teams_id = :id';
                break;
            case EmailTarget::TeamGroup:
                $join = 'CROSS JOIN users2team_groups ON (users2team_groups.userid = users.userid)';
                $filter = 'AND users2team_groups.groupid = :id';
                break;
            case EmailTarget::Admins:
                $join = 'CROSS JOIN users2teams ON (users2teams.users_id = users.userid)';
                $filter = sprintf('AND users2teams.groups_id = %d', Usergroup::Admin->value);
                break;
            case EmailTarget::AdminsOfTeam:
                $join = 'CROSS JOIN users2teams ON (users2teams.users_id = users.userid)';
                $filter = sprintf('AND users2teams.groups_id = %d AND users2teams.teams_id = :id', Usergroup::Admin->value);
                break;
            case EmailTarget::Sysadmins:
                $join = '';
                $filter = 'AND users.is_sysadmin = 1';
                break;
            case EmailTarget::BookableItem:
                $join = 'CROSS JOIN team_events ON (team_events.userid = users.userid)';
                $filter = 'AND team_events.start BETWEEN NOW() - INTERVAL 2 MONTH AND NOW() + INTERVAL 1 MONTH AND team_events.item = :id';
                break;
            default:
                $join = '';
                $filter = '';
        }
        $where = 'WHERE users.validated = 1 AND users.archived = 0';
        $sql = sprintf('%s %s %s %s', $select, $join, $where, $filter);
        $Db = Db::getConnection();
        $req = $Db->prepare($sql);
        if ($target->needsId()) {
            $req->bindParam(':id', $targetId, PDO::PARAM_INT);
        }
        $Db->execute($req);

        return $req->fetchAll();
    }
}
