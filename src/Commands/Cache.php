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

use function implode;
use function sprintf;

/**
 * Handle the cached Twig files
 */
#[AsCommand(name: 'cache:all')]
class Cache extends Command
{
    protected array $actions = array('clear', 'warm');

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Manage all cache folders')
            ->setHelp('This command will act upon all cache folders of the application.')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: clear or warm', null, $this->actions);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('action')) {
            case 'warm':
                $output->writeln('<error>Error: No warm action available.</error>');
                return Command::INVALID;
            case 'clear':
                new NginxCache()->clear();
                new ParentCache()->clear();
                return Command::SUCCESS;
            default:
                $output->writeln(sprintf('<error>Error: Invalid action argument. Available actions: %s</error>', implode(', ', $this->actions)));
                return Command::INVALID;
        }
    }
}
