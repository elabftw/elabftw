<?php
/**
 * \Elabftw\Elabftw\Email
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_MailTransport;
use Swift_SendmailTransport;
use Elabftw\Core\Config;
use Elabftw\Core\Users;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;
use Exception;

/**
 * Email service
 */
class Email
{
    /** @var Config $Config instance of Config */
    public $Config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->Config = $config;
    }
    /**
     * Returns Swift_Mailer instance and chooses between sendmail and smtp
     * @return Swift_Mailer
     */
    public function getMailer()
    {

        // Choose mail transport method; either smtp or sendmail
        switch ($this->Config->configArr['mail_method']) {

            // Use SMTP Server
            case 'smtp':
                if ($this->Config->configArr['smtp_encryption'] === 'none') {
                    $transport = Swift_SmtpTransport::newInstance(
                        $this->Config->configArr['smtp_address'],
                        $this->Config->configArr['smtp_port']
                    );
                } else {
                    $transport = Swift_SmtpTransport::newInstance(
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
                break;

            // Use php mail function
            case 'php':
                $transport = Swift_MailTransport::newInstance();
                break;

            // Use locally installed MTA (aka sendmail); Default
            default:
                $transport = Swift_SendmailTransport::newInstance($this->Config->configArr['sendmail_path'] . ' -bs');
                break;
        }

        $mailer = Swift_Mailer::newInstance($transport);
        return $mailer;
    }

    /**
     * Send a test email
     *
     * @param string $email
     * @return bool
     */
    public function testemailSend($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Bad email!');
        }

        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        $message = \Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(_('[eLabFTW] Test email'))
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => 'Admin eLabFTW'))
        // Give it a body
        ->setBody(_('Congratulations, you correctly configured eLabFTW to send emails :)') . $footer);
        // generate Swift_Mailer instance
        $mailer = $this->getMailer();

        return (bool) $mailer->send($message);
    }

    /**
     * Send a mass email to all users
     *
     * @param string $subject
     * @param string $body
     * @return int number of emails sent
     */
    public function massEmail($subject, $body)
    {
        if (empty($subject)) {
            $subject = 'No subject';
        }

        // get all email adresses
        $Users = new Users();
        $UsersArr = $Users->getAllEmails();
        $to = array();
        foreach ($UsersArr as $user) {
            $to[] = $user['email'];
        }

        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        ->setTo($to)
        ->setBody($body . $footer);
        $mailer = $this->getMailer();

        return $mailer->send($message);
    }
}
