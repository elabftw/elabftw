<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\FsTools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * For cache related actions
 */
class CacheClear extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'cache:clear';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Remove files in cache folder')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Temporary files can be stored in the cache folder. This command will remove its contents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isVerbose()) {
            $output->writeln(array(
                'Clearing cache',
                '==============',
            ));
        }
        FsTools::deleteCache();
        if ($output->isVerbose()) {
            $output->writeln('Cache cleared!');
        }
        return 0;
    }
}
