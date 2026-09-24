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

use Elabftw\Elabftw\Migrations;
use Elabftw\Elabftw\SchemaVersionChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function sprintf;

/**
 * Check the current legacy schema and pending UUIDv7 migrations.
 */
#[AsCommand(name: 'db:check')]
final class CheckDatabase extends Command
{
    private readonly Migrations $Migrations;

    public function __construct(private int $currentSchema, ?Migrations $Migrations = null)
    {
        $this->Migrations = $Migrations ?? Migrations::getDefault();
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Check the database version')
            ->setHelp('Check the legacy schema baseline and whether UUIDv7 migrations are pending.');
    }

    /**
     * @return int 0 if no need to upgrade, 1 if need to upgrade
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pending = $this->Migrations->getPendingCount();
        $output->writeln(array(
            'Database check',
            '==============',
            sprintf('Legacy schema: %d / %d', $this->currentSchema, SchemaVersionChecker::REQUIRED_SCHEMA),
            sprintf('Pending UUIDv7 migrations: %d', $pending),
        ));
        if ($this->currentSchema === SchemaVersionChecker::REQUIRED_SCHEMA && $pending === 0) {
            $output->writeln('No upgrade required.');
            return Command::SUCCESS;
        }

        $output->writeln('An upgrade is required.');
        return Command::FAILURE;
    }
}
