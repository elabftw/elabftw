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

use Elabftw\Elabftw\Sql;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Use this to revert a specific schema
 */
#[AsCommand(name: 'db:revert')]
class RevertSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Allow reverting a specific schema upgrade.')
            ->setHelp("Use this command to revert a specific schema. Example to revert schema 116: 'db:revert 116'.")
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore errors during execution')
            ->addArgument('number', InputArgument::REQUIRED, 'Schema number to revert');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Sql = new Sql($this->fs, $output);
        $Sql->execFile(sprintf('schema%d-down.sql', (int) $input->getArgument('number')), $input->getOption('force'));
        return Command::SUCCESS;
    }
}
