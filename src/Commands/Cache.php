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

use Elabftw\Storage\Cache\NginxCache;
use Elabftw\Storage\Cache\ParentCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Handle the cached Twig files
 */
#[AsCommand(name: 'cache')]
class Cache extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Manage cache folders')
            ->setHelp('')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: clear or warm', null, array('clear', 'warm'));
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('action') === 'warm') {
            $output->writeln('<error>Error: No warm action for this cache folder.</error>');
            return Command::INVALID;
        }
        if ($input->getArgument('action') === 'clear') {
            new NginxCache()->clear();
            new ParentCache()->clear();
        }
        return Command::SUCCESS;
    }
}
