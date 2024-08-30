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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cleanup the database: look for orphans leftover from past bugs
 */
#[AsCommand(name: 'db:clean')]
class CleanDatabase extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Clean the database from orphans')
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
