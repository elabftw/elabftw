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
use Override;

/**
 * To remove deleted items completely
 */
#[AsCommand(name: 'prune:items')]
final class PruneItems extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Remove deleted items')
            ->setHelp('Remove items from the database marked as deleted');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Pruning items',
            '=============',
        ));
        $Cleaner = new EntityPruner(EntityType::Items);
        $cleanedNumber = $Cleaner->cleanup();
        $output->writeln(sprintf('Removed %d items', $cleanedNumber));
        return Command::SUCCESS;
    }
}
