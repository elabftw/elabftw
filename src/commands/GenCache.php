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

use Elabftw\Services\CacheGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate the cached Twig files
 */
class GenCache extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:gencache';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Generate the cached Twig files')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Loop through all the templates and ask Twig to load it, so the cache file is generated in cache/twig.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Generating Twig cache files',
            '===========================',
        ));
        $Generator = new CacheGenerator();
        $Generator->generate();
        $output->writeln(array(
            'Success. All the templates are now cached by Twig.',
        ));
        return 0;
    }
}
