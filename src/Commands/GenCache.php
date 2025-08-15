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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Generate the cached Twig files
 */
#[AsCommand(name: 'dev:gencache')]
final class GenCache extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Generate the cached Twig files')
            ->setHelp('Loop through all the templates and ask Twig to load it, so the cache file is generated in cache/twig.');
    }

    #[Override]
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
