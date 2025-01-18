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

use Elabftw\Elabftw\Update;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check the the current schema version versus the required one
 */
#[AsCommand(name: 'db:check')]
class CheckDatabase extends Command
{
    public function __construct(private int $currentSchema)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Check the database version')
            ->setHelp('This command allows you to compare the database version with the current required schema.');
    }

    /**
     * Execute
     *
     * @return int 0 if no need to upgrade, 1 if need to upgrade
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(array(
            'Database check',
            '==============',
            sprintf('Current version: %d', $this->currentSchema),
            sprintf('Required version: %d', Update::REQUIRED_SCHEMA),
        ));
        if ($this->currentSchema === Update::REQUIRED_SCHEMA) {
            $output->writeln('No upgrade required.');
            return Command::SUCCESS;
        }

        $output->writeln('An upgrade is required.');
        return Command::FAILURE;
    }
}
