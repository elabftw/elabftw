<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Services\UploadsPruner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * To remove deleted files completely
 */
class PruneUploads extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'uploads:prune';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Remove deleted uploaded files')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Remove uploaded files marked as deleted from the filesystem and from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isVerbose()) {
            $output->writeln(array(
                'Pruning uploads',
                '===============',
            ));
        }
        $Cleaner = new UploadsPruner();
        $cleanedNumber = $Cleaner->cleanup();
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Removed %d uploads', $cleanedNumber));
        }
        return 0;
    }
}
