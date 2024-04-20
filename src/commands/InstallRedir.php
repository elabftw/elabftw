<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Delete this after a few releases with the new command
 */
#[AsCommand(name: 'db:install')]
class InstallRedir extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Use "bin/install start" instead')
            ->setHelp('Use "bin/install start" instead');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<error>⚠ This command has moved!\n→ Use 'bin/init db:install' instead.</error>");
        return Command::FAILURE;
    }
}
