<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Storage\ParentCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear the cache folder
 */
#[AsCommand(name: 'cache:clear')]
class CacheClear extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Remove files in cache folder')
            ->setHelp('Temporary files can be stored in the cache folder. This command will remove its contents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Clearing cache',
            '==============',
        ));

        $storage = new ParentCache();
        $storage->destroy();
        $output->writeln('Cache cleared!');
        return Command::SUCCESS;
    }
}
