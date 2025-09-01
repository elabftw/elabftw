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
use Override;

/**
 * Cleanup the database: look for orphans leftover from past bugs
 */
#[AsCommand(name: 'prune:revisions')]
final class PruneRevisions extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Remove revisions from the database if there are too many')
            ->setHelp('Make sure we are not storing more revisions than what we want. Will align on the configured max_revisions value of config.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Revisions pruning starting',
            '==========================',
        ));
        $Cleaner = new RevisionsCleaner();
        $Cleaner->prune();
        return Command::SUCCESS;
    }
}
