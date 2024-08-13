<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Import\TeamEln;
use Elabftw\Interfaces\StorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import a full team
 */
#[AsCommand(name: 'teams:import')]
class ImportTeams extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Import everything from a .eln created with teams:export command')
            ->setHelp('Everything is recreated in a pre-existing or empty team.')
            ->addArgument('userid', InputArgument::REQUIRED, 'Importer userid')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import present in cache/elab folder')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Process the archive, but do not actually import things, display what would be done');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userid = (int) $input->getArgument('userid');
        $teamid = (int) $input->getArgument('teamid');
        $filePath = $this->Fs->getPath((string) $input->getArgument('file'));

        $Importer = new TeamEln($userid, $teamid, $filePath, $this->Fs->getFs());
        if ($input->getOption('dry-run')) {
            $result = $Importer->dryRun();
            $output->writeln(sprintf('Found %d main entities to import.', $result['parts']));
            return Command::SUCCESS;
        }
        $Importer->import();

        $output->writeln(sprintf('Everything successfully imported for team with ID %d.', $teamid));

        return Command::SUCCESS;
    }
}
