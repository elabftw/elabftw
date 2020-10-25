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
 * Cleanup the database: look for orphans leftover from past bugs
 */
class PruneRevisions extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'rev:prune';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Remove revisions from the database if there are too many')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Make sure we are not storing more revisions than what we want. Will align on the configured max_revisions value of config.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Revisions pruning starting',
            '==========================',
        ));
        $Cleaner = new RevisionsCleaner();
        $Cleaner->prune();
        return 0;
    }
}
