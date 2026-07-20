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
use Elabftw\Storage\Cache\TwigCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Handle the cached Twig files
 */
#[AsCommand(name: 'cache:twig')]
final class CacheTwig extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Manage Twig cache directory')
            ->setHelp('Twig stores a cache of html templates. This command helps manage it.')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: clear or warm', null, array('clear', 'warm'));
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('action') === 'warm') {
            $dir = TwigCache::getFolder();
            $TwigCache = new TwigCacheGenerator($dir, $output);
            $TwigCache->warm();
        }
        if ($input->getArgument('action') === 'clear') {
            new TwigCache()->clear();
        }
        return 0;
    }
}
