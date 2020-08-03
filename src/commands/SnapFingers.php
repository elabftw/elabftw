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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Make the database lighter by removing half of the revisions
 * This is implemented because before 59efc7656 all quicksave actions would
 * lead to a revision creation. See #623
 */
class SnapFingers extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'thanos:snap';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Remove half of the stored revisions')

            // the full command description shown when running the command with
            // the "--help" option
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
        return 0;
    }
}
