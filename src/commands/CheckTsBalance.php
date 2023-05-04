<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Models\Config;
use Elabftw\Services\Email;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

/**
 * Look at the timestamp balance and notify sysadmin if it's too low
 */
class CheckTsBalance extends Command
{
    private const THRESHOLD = 20;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'notifications:tsbalance';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription("Check the balance on timestamps left and create a notification if it's too low")
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Look at the column ts_balance from Config table and create a notification to sysadmins if it's too low.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Config = Config::getConfig();
        $tsBalance = (int) $Config->configArr['ts_balance'];
        if ($tsBalance === 0) {
            return 0;
        }
        if ($tsBalance < self::THRESHOLD) {
            $Logger = new Logger('elabftw');
            $Logger->pushHandler(new ErrorLogHandler());
            $Email = new Email(
                new Mailer(Transport::fromDsn($Config->getDsn())),
                $Logger,
                $Config->configArr['mail_from'],
            );
            $Email->notifySysadminsTsBalance($tsBalance);
        }
        return 0;
    }
}
