<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Migrations;
use Elabftw\Enums\Action;
use Elabftw\Models\Config;
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
 * For dev purposes: force legacy schema state or UUIDv7 migration state.
 */
#[AsCommand(name: 'dev:forceschema')]
final class ForceSchema extends Command
{
    private readonly Migrations $Migrations;

    public function __construct(?Migrations $Migrations = null)
    {
        $this->Migrations = $Migrations ?? Migrations::getDefault();
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Force database migration state without executing SQL')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Legacy schema number or UUIDv7 migration identifier')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Mark a UUIDv7 migration as pending instead of applied')
            ->setHelp('Legacy numbers update config.schema. UUIDv7 identifiers are added to or removed from schema_migrations.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifier = (string) $input->getArgument('identifier');
        if (ctype_digit($identifier)) {
            $schemaNumber = (int) $identifier;
            $Config = Config::getConfig();
            $Config->patch(Action::Update, array('schema' => $schemaNumber));
            $output->writeln(sprintf('Changing legacy schema to %d', $schemaNumber));
            return Command::SUCCESS;
        }

        if (!Migrations::isUuidV7($identifier)) {
            $output->writeln('<error>Identifier must be a UUIDv7 or a legacy schema number.</error>');
            return Command::INVALID;
        }
        if ($this->Migrations->getFilename($identifier) === null) {
            $output->writeln(sprintf('<error>Migration file for %s was not found.</error>', $identifier));
            return Command::FAILURE;
        }

        if ($input->getOption('remove')) {
            $this->Migrations->removeApplied($identifier);
            $output->writeln(sprintf('Marked migration %s as pending', $identifier));
            return Command::SUCCESS;
        }

        if (!$this->Migrations->isApplied($identifier)) {
            $this->Migrations->recordApplied($identifier, $this->Migrations->nextBatch());
        }
        $output->writeln(sprintf('Marked migration %s as applied', $identifier));
        return Command::SUCCESS;
    }
}
