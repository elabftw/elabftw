<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\Email;
use Elabftw\Services\EmailNotifications;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send the notifications emails
 */
#[AsCommand(name: 'notifications:send')]
class SendNotifications extends Command
{
    public function __construct(private Email $Email)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Send the notifications emails')
            ->setHelp('Look for all notifications that need to be sent by email and send them');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Notifications = new EmailNotifications($this->Email);
        $count = $Notifications->sendEmails();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Sent %d emails', $count));
        }

        return Command::SUCCESS;
    }
}
