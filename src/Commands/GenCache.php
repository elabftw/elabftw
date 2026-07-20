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

use Elabftw\Services\TwigCacheGenerator;
use Elabftw\Storage\TwigCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Generate the cached Twig files
 */
#[AsCommand(name: 'cache:twig')]
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
        $dir = TwigCache::getFolder();
        $output->writeln(array(
            sprintf('Generating Twig cache files in %s', $dir),
        ));
        $Generator = new TwigCacheGenerator($dir);
        $Generator->generate();
        $output->writeln(array(
            'Success. All the templates are now cached by Twig.',
        ));
        return 0;
    }
}
