<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Sql;
use Elabftw\Models\Config;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Use this command to revert many schemas at once
 * Performs a loop from current schema to indicated schema
 */
#[AsCommand(name: 'db:revertto')]
final class RevertToSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Revert the database schema down to a specific version.')
            ->setHelp("Use this command to revert many schemas at once. Example: 'db:revertto 170' will revert from current schema down to 170.")
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore errors during execution')
            ->addArgument('target', InputArgument::REQUIRED, 'Target schema version to revert to');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = (int) $input->getArgument('target');
        $force = $input->getOption('force');

        $Config = Config::getConfig();
        $current = (int) $Config->configArr['schema'];

        if ($target >= $current) {
            $output->writeln(sprintf('<error>Target schema (%d) must be lower than current schema (%d).</error>', $target, $current));
            return Command::FAILURE;
        }

        $sql = new Sql($this->fs, $output);

        for ($version = $current; $version >= $target; $version--) {
            $filename = sprintf('schema%d-down.sql', $version);
            $output->writeln("Reverting schema $version with $filename...");
            $sql->execFile($filename, $force);
        }

        $output->writeln(sprintf('<info>→ Successfully reverted from schema: %d to schema: %d included.</info>', $current, $target));

        return Command::SUCCESS;
    }
}
