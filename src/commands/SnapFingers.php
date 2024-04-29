<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\RevisionsCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Make the database lighter by removing half of the revisions
 * This is implemented because before 59efc7656 all quicksave actions would
 * lead to a revision creation. See #623
 */
#[AsCommand(name: 'thanos:snap')]
class SnapFingers extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Remove half of the stored revisions')
            ->setHelp('A bug fixed in version 1.8.3 would lead to the revisions tables to grow very fast. This is a method to reduce the size of those tables.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Snapping fingers',
            '================',
        ));
        $Cleaner = new RevisionsCleaner();
        $Cleaner->cleanup();
        $output->writeln(array(
            '*Snap*',
            'Perfectly balanced. As all things should be.',
        ));
        return Command::SUCCESS;
    }
}
