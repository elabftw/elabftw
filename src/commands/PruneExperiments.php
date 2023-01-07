<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Models\AbstractEntity;
use Elabftw\Services\EntityPruner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * To remove deleted files completely
 */
class PruneExperiments extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'prune:experiments';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Remove deleted experiments definitively')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Remove experiments marked as deleted from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isVerbose()) {
            $output->writeln(array(
                'Pruning experiments',
                '===================',
            ));
        }
        $Cleaner = new EntityPruner(AbstractEntity::TYPE_EXPERIMENTS);
        $cleanedNumber = $Cleaner->cleanup();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Removed %d experiments', $cleanedNumber));
        }
        return 0;
    }
}
