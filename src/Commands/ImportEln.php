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

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Import\TrustedEln;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Users\UltraAdmin;
use Elabftw\Models\Users\Users;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

/**
 * Import an ELN archive
 */
#[AsCommand(name: 'import:eln')]
final class ImportEln extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Import everything from a .eln')
            ->setHelp('Every node of type Dataset is created. If the .eln has been produced by eLabFTW, everything will be imported, otherwise the results may vary. Use verbose flags (-v or -vv) to get more information about what is happening. If a userid is provided through the --userid option, all entries will be created with that user as the author.  Otherwise, entry ownership will be determined by user account email addresses and userid values mapped accordingly. Any users in the .eln that do not exist on this server will be created as needed.')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import. Must be present in /elabftw/exports/ folder in the container')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addOption('userid', 'u', InputOption::VALUE_REQUIRED, 'Target user ID')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Force entity type. Values: ' . implode(', ', array_map(fn($case) => $case->value, EntityType::cases())))
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Force category: provide a category ID that belongs to the team')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Process the archive, but do not actually import things, display what would be done')
            ->addOption('checksum', 'k', InputOption::VALUE_NEGATABLE, 'Verify file integrity before import', true)
            ->addOption('checksum-error-skip', 'e', InputOption::VALUE_NEGATABLE, 'Skip file import if integrity check failed', false);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teamid = (int) $input->getArgument('teamid');
        $filePath = $this->Fs->getPath((string) $input->getArgument('file'));

        $logger = new ConsoleLogger($output);
        $UploadedFile = new UploadedFile($filePath, 'input.eln', test: true);
        $user = new UltraAdmin(team: $teamid);
        $infoTrailer = '';
        $requesterIsAuthor = false;
        if ($input->getOption('userid')) {
            $user = new Users((int) $input->getOption('userid'), $teamid);
            $infoTrailer = sprintf(' and User with ID %s', $input->getOption('userid'));
            $requesterIsAuthor = true;
        }
        $entityType = null;
        if ($input->getOption('type')) {
            $entityType = EntityType::tryFrom((string) $input->getOption('type'));
        }
        $defaultCategory = null;
        if ($input->getOption('category')) {
            $defaultCategory = (int) $input->getOption('category');
        }
        $Importer = new TrustedEln(
            $user,
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $UploadedFile,
            $this->Fs->getFs(),
            $logger,
            $entityType,
            category: $defaultCategory,
            verifyChecksum: (bool) $input->getOption('checksum'),
            checksumErrorSkip: (bool) $input->getOption('checksum-error-skip'),
        );
        if ($input->getOption('dry-run')) {
            // this is necessary so -vv isn't required to get dry run info
            $output->setVerbosity(Output::VERBOSITY_VERY_VERBOSE);
            $logger->info(sprintf('%d records found', $Importer->getCount()));
            return Command::SUCCESS;
        }
        $Importer->requesterIsAuthor = $requesterIsAuthor;
        $Importer->import();
        $logger->info(sprintf('Import finished for Team with ID %d%s', $teamid, $infoTrailer));
        /** @var QuestionHelper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('[*] Delete ELN file? (y/N) ', false);
        if ($helper->ask($input, $output, $question)) {
            $this->Fs->getFs()->delete((string) $input->getArgument('file'));
            $logger->info(sprintf('Deleted input file: %s', $filePath));
        }

        return Command::SUCCESS;
    }
}
