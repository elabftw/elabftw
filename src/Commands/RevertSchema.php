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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
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
 * Use this to revert a specific schema
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
        $this->setDescription('Roll back the last migration batch, or selected recent migrations')
            ->setHelp('Without arguments, roll back the last batch. Use --step=N for the last N migrations, or an ID for the latest migration. Legacy numbers remain supported.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore errors when reverting a legacy numbered schema only')
            ->addOption('step', null, InputOption::VALUE_REQUIRED, 'Number of recent migrations to revert')
            ->addArgument('number', InputArgument::OPTIONAL, 'Latest migration ID, or current legacy schema number');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('number');
        $name = $argument === null ? null : (string) $argument;
        $step = $input->getOption('step');
        if ($step !== null && ($name !== null || !ctype_digit((string) $step) || (int) $step < 1)) {
            throw new ImproperActionException('Use a positive --step value without a migration argument.');
        }
        $Migrations = new Migrations($this->fs, $output);
        if ($name !== null && ctype_digit($name)) {
            $Migrations->revertLegacy($name, $input->getOption('force'));
        } else {
            if ($input->getOption('force')) {
                throw new ImproperActionException('--force is only supported for legacy schemas. Repair timestamped migration history explicitly with dev:forceschema.');
            }
            $count = $Migrations->rollback($name, (int) $step);
            $output->writeln(sprintf('Reverted %d migration(s).', $count));
        }
        $Config = Config::getConfig();
        $Config->configArr = $Config->readAll();
        return Command::SUCCESS;
    }
}
