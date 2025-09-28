<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Remove all notifications
 */
#[AsCommand(name: 'notifications:clear')]
final class ClearNotifications extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Clear all notifications, past and future.')
            ->setHelp('Will remove all notifications from the notifications mysql table.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Db = Db::getConnection();
        $Db->q('DELETE FROM notifications');
        $output->writeln('All notifications removed.');
        return Command::SUCCESS;
    }
}
