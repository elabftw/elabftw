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
use Elabftw\Models\Compounds;
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
use Elabftw\Models\Config;
use Elabftw\Models\Items;
use Elabftw\Services\Fingerprinter;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Services\PubChemImporter;
use GuzzleHttp\Client;
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
            ->setHelp('Column names that will match: cas, chebi_id, chembl_id, dea_number, drugbank_id, dsstox_id, ec_number, hmdb_id, inchi, inchikey, iupacname, is_antibiotic_precursor, is_cmr, is_controlled, is_corrosive, is_drug_precursor, is_explosive, is_explosive_precursor, is_flammable, is_gas_under_pressure, is_hazardous2env, is_hazardous2health, is_nano, is_oxidising, is_radioactive, is_serious_health_hazard, is_toxic, kegg_id, metabolomics_wb_id, molecularformula, molecularweight, name, nci_code, nikkaji_number, pharmgkb_id, pharos_ligand_id, pubchemcid, rxcui, smiles, unii, wikidata, wikipedia. A column named "comment" will be added to the main text of the associated resource if a resource is associated with it.')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import. Must be present in /elabftw/exports folder in the container')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addOption('userid', 'u', InputOption::VALUE_REQUIRED, 'Target user ID')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Process the file, but do not actually import things, display what would be done')
            ->addOption('use-pubchem', 'p', InputOption::VALUE_NONE, 'Use PubChem to complete information. Use the CAS number or Pubchem CID to fetch data from PubChem and complement existing data.')
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
        $Config = Config::getConfig();
        $Fingerprinter = new NullFingerprinter();
        $httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
        if (Config::boolFromEnv('USE_FINGERPRINTER')) {
            $Fingerprinter = new Fingerprinter($httpGetter, Config::fromEnv('FINGERPRINTER_URL'));
        }

        $usePubchem = (bool) $input->getOption('use-pubchem');
        $pubChemImporter = null;
        if ($usePubchem) {
            $output->writeln('[info] Using Pubchem to complete data: this might take a long time.');
            $pubChemImporter = new PubChemImporter($httpGetter);
        }
        $Items = new Items($user);
        $Compounds = new Compounds($httpGetter, $user, $Fingerprinter);
        $Importer = new CompoundsCsv(
            $output,
            $Items,
            $UploadedFile,
            $Compounds,
            $resourceCategory,
            $pubChemImporter,
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
