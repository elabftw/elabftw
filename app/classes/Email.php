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

use \Swift_Mailer;
use \Swift_SmtpTransport;
use \Swift_MailTransport;
use \Swift_SendmailTransport;
use \Defuse\Crypto\Crypto as Crypto;
use \Defuse\Crypto\Key as Key;

/**
 * Email service
 */
class Email
{
    /** instance of Config */
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

                $transport->setUsername($this->Config->configArr['smtp_username'])
                ->setPassword(Crypto::decrypt(
                    $this->Config->configArr['smtp_password'],
                    Key::loadFromAsciiSafeString(SECRET_KEY)
                ));
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
}
