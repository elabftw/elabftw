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

use Elabftw\Services\DatabaseCleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cleanup the database: look for orphans leftover from past bugs
 */
class CleanDatabase extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'db:clean';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Clean the database from orphans')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Some bugs in the past version might have left some things behind. To allow for a smooth upgrade, it is best to run this command before updating.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Database cleanup starting',
            '=========================',
        ));
        $Cleaner = new DatabaseCleaner();
        $Cleaner->cleanup();
        return 0;
    }
}
