<?php
/**
 * \Elabftw\Elabftw\Sysconfig
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Swift_Message;

/**
 * Sysadmin configuration
 */
class Sysconfig
{
    /** instance of Email */
    private $Email;

    /**
     * Constructor
     *
     * @param Email $email
     */
    public function __construct(Email $email)
    {
        $this->Email = $email;
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
        ->setFrom(array($this->Email->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => 'Admin eLabFTW'))
        // Give it a body
        ->setBody(_('Congratulations, you correctly configured eLabFTW to send emails :)') . $footer);
        // generate Swift_Mailer instance
        $mailer = $this->Email->getMailer();

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
        $message = Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom(array($this->Email->Config->configArr['mail_from'] => 'eLabFTW'))
        ->setTo($to)
        ->setBody($body . $footer);
        $mailer = $this->Email->getMailer();

        return $mailer->send($message);
    }
}
