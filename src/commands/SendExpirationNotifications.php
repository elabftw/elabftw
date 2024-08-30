<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\Email;
use Elabftw\Services\ExpirationNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send the notifications emails for accounts close to expiration
 */
#[AsCommand(name: 'notifications:send-expiration')]
class SendExpirationNotifications extends Command
{
    public function __construct(private Email $Email)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Send notification emails to user accounts close to end of validity, and to their admins')
            ->setHelp('Look for all users where the valid_until attribute is close to expiration, and warn them and their Admins. This command runs weekly and will warn 4 weeks in advance.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Notifications = new ExpirationNotifier($this->Email);
        $count = $Notifications->sendEmails();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Sent %d emails', $count));
        }

        return Command::SUCCESS;
    }
}
