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

use Elabftw\Enums\EntityType;
use Elabftw\Services\EntityPruner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * To remove deleted files completely
 */
#[AsCommand(name: 'prune:experiments')]
class PruneExperiments extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Remove deleted experiments definitively')
            ->setHelp('Remove experiments marked as deleted from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Pruning experiments',
            '===================',
        ));
        $Cleaner = new EntityPruner(EntityType::Experiments);
        $cleanedNumber = $Cleaner->cleanup();
        $output->writeln(sprintf('Removed %d experiments', $cleanedNumber));
        return Command::SUCCESS;
    }
}
