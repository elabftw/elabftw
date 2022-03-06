<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Models\Config;
use Elabftw\Services\Email;
use Elabftw\Services\EmailNotifications;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send the notifications emails
 */
class SendNotifications extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'notifications:send';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Send the notifications emails')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Look for all notifications that need to be sent by email and send them');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Logger = new Logger('elabftw');
        $Logger->pushHandler(new ErrorLogHandler());
        $Email = new Email(Config::getConfig(), $Logger);
        $Notifications = new EmailNotifications($Email);
        $count = $Notifications->sendEmails();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Sent %d emails', $count));
        }

        return 0;
    }
}
