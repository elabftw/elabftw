<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Factories;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Models\Config;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use function urlencode;

class MailerFactory
{
    public function __construct(private Config $Config)
    {
    }

    /**
     * Return Mailer instance
     */
    public function getMailer(): MailerInterface
    {
        $username = '';
        $password = '';
        if ($this->Config->configArr['smtp_password']) {
            $username = $this->Config->configArr['smtp_username'];
            $password = Crypto::decrypt(
                $this->Config->configArr['smtp_password'],
                Key::loadFromAsciiSafeString($this->Config::fromEnv('SECRET_KEY'))
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
