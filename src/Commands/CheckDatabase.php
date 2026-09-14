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

use Elabftw\Elabftw\SchemaVersionChecker;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Migrations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function sprintf;
use function dirname;
use function array_diff;
use function array_keys;

/**
 * Check the the current schema version versus the required one
 */
#[AsCommand(name: 'db:check')]
final class CheckDatabase extends Command
{
    public function __construct(private int $currentSchema, private ?Migrations $migrations = null)
    {
        parent::__construct();
    }

    #[Override]
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
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(array(
            'Database check',
            '==============',
            sprintf('Legacy baseline: %d', $this->currentSchema),
            sprintf('Required legacy baseline: %d', SchemaVersionChecker::REQUIRED_SCHEMA),
        ));
        if ($this->currentSchema > SchemaVersionChecker::REQUIRED_SCHEMA) {
            $output->writeln('<error>The database is newer than this checkout.</error>');
            return Command::INVALID;
        }
        if ($this->currentSchema < SchemaVersionChecker::REQUIRED_SCHEMA) {
            $output->writeln('An upgrade is required.');
            return Command::FAILURE;
        }
        $Migrations = $this->migrations ?? new Migrations(FsTools::getFs(dirname(__DIR__) . '/sql'), $output);
        $applied = $Migrations->applied();
        $available = $Migrations->available();
        foreach ($available as $name) {
            $output->writeln(sprintf('%s  %s', isset($applied[$name]) ? 'Applied (batch ' . $applied[$name] . ')' : 'Pending', $name));
        }
        $unknown = array_diff(array_keys($applied), $available);
        foreach ($unknown as $name) {
            $output->writeln('<error>Applied migration missing from checkout: ' . $name . '</error>');
        }
        if ($unknown !== array()) {
            return Command::INVALID;
        }
        if (!$Migrations->isInstalled() || $Migrations->pending() !== array()) {
            $output->writeln('An upgrade is required.');
            return Command::FAILURE;
        }
        $output->writeln('No upgrade required.');
        return Command::SUCCESS;
    }
}
