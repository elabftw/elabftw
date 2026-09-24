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

use Elabftw\Elabftw\Migrations;
use Elabftw\Elabftw\Sql;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function ctype_digit;
use function sprintf;

/**
 * Revert a migration or the latest UUIDv7 migration batch.
 */
#[AsCommand(name: 'db:revert')]
final class RevertSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Revert a database migration.')
            ->setHelp('Pass a UUIDv7 to revert one migration, a legacy schema number to revert an old migration, or no argument to revert the latest UUIDv7 batch.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore errors during execution')
            ->addArgument('migration', InputArgument::OPTIONAL, 'UUIDv7 migration identifier or legacy schema number');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifier = $input->getArgument('migration');
        $Sql = new Sql($this->fs, $output);

        if ($identifier !== null && ctype_digit((string) $identifier)) {
            $Sql->execFile(sprintf('schema%d-down.sql', (int) $identifier), $input->getOption('force'));
            return Command::SUCCESS;
        }

        $Migrations = new Migrations($this->fs);
        if ($identifier !== null) {
            return $this->revertMigration((string) $identifier, $Migrations, $Sql, $input->getOption('force'), $output);
        }

        $batch = $Migrations->getLastBatch();
        if ($batch === array()) {
            $output->writeln('No UUIDv7 migration batch to revert.');
            return Command::SUCCESS;
        }
        foreach ($batch as $migration) {
            $status = $this->revertMigration($migration, $Migrations, $Sql, $input->getOption('force'), $output);
            if ($status !== Command::SUCCESS) {
                return $status;
            }
        }
        return Command::SUCCESS;
    }

    private function revertMigration(string $migration, Migrations $Migrations, Sql $Sql, bool $force, OutputInterface $output): int
    {
        if (!Migrations::isUuidV7($migration)) {
            $output->writeln('<error>Migration identifier must be a UUIDv7 or a legacy schema number.</error>');
            return Command::INVALID;
        }
        if (!$Migrations->isApplied($migration)) {
            $output->writeln(sprintf('<error>Migration %s is not applied.</error>', $migration));
            return Command::FAILURE;
        }
        $filename = $Migrations->getFilename($migration);
        if ($filename === null) {
            $output->writeln(sprintf('<error>Migration file for %s was not found.</error>', $migration));
            return Command::FAILURE;
        }

        $Sql->execFile(Migrations::getDownFilename($filename), $force);
        $Migrations->removeApplied($migration);
        $output->writeln(sprintf('<info>Reverted migration %s.</info>', $migration));
        return Command::SUCCESS;
    }
}
