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

use Elabftw\Import\CompoundsCsv;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\UltraAdmin;
use Elabftw\Models\Users;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

use function sprintf;

/**
 * Import a CSV into compounds
 */
#[AsCommand(name: 'import:compounds')]
final class ImportCompoundsCsv extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Import compounds from a CSV file')
            ->setHelp('Column names that will match: name, smiles, inchi, inchikey, cas, pubchemcid, molecularformula, iupacname')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import. Must be present in /elabftw/exports folder in the container')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addOption('userid', 'u', InputOption::VALUE_REQUIRED, 'Target user ID')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Process the file, but do not actually import things, display what would be done')
            ->addOption('create-resource', 'c', InputOption::VALUE_REQUIRED, 'Create a resource linked to that compound with the category ID provided');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teamid = (int) $input->getArgument('teamid');
        $filePath = $this->Fs->getPath((string) $input->getArgument('file'));

        $logger = new ConsoleLogger($output);
        $UploadedFile = new UploadedFile($filePath, 'input.csv', test: true);
        $user = new UltraAdmin(team: $teamid);
        $infoTrailer = '';
        if ($input->getOption('userid')) {
            $user = new Users((int) $input->getOption('userid'), $teamid);
            $infoTrailer = sprintf(' and User with ID %s', $input->getOption('userid'));
        }
        $resourceCategory = null;
        if ($input->getOption('create-resource')) {
            $resourceCategory = (int) $input->getOption('create-resource');
        }
        $Importer = new CompoundsCsv(
            $user,
            $UploadedFile,
            $resourceCategory,
        );
        if ($input->getOption('dry-run')) {
            // this is necessary so -vv isn't required to get dry run info
            $output->setVerbosity(Output::VERBOSITY_VERY_VERBOSE);
            $logger->info(sprintf('%d records found', $Importer->getCount()));
            return Command::SUCCESS;
        }

        $count = $Importer->import();
        $logger->info(sprintf('Done importing %d records', $count));
        $logger->info(sprintf('Import finished for Team with ID %d%s', $teamid, $infoTrailer));
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('[*] Delete imported file? (y/N) ', false);
        /** @phpstan-ignore-next-line ask method is part of QuestionHelper which extends HelperInterface */
        if ($helper->ask($input, $output, $question)) {
            $this->Fs->getFs()->delete((string) $input->getArgument('file'));
            $logger->info(sprintf('Deleted input file: %s', $filePath));
        }

        return Command::SUCCESS;
    }
}
