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
use Elabftw\Elabftw\Sql;
use Elabftw\Elabftw\Update;
use Elabftw\Models\Config;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function dirname;
use function sprintf;

/**
 * Update the database schema
 */
#[AsCommand(name: 'db:update')]
final class UpdateDatabase extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Update the database structure')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore errors during execution')
            ->setHelp('Apply legacy numeric schemas and all pending UUIDv7 migrations.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @psalm-suppress PossiblyNullReference */
        $command = $this->getApplication()->find('db:check');

        $cmdInput = new ArrayInput(array('command' => 'db:check'));
        $returnCode = $command->run($cmdInput, $output);

        if ($returnCode === Command::FAILURE) {
            $output->writeln(array(
                'Database update starting',
                '========================',
            ));

            $Config = Config::getConfig();
            $sqlFs = new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql'));
            $Migrations = new Migrations($sqlFs);
            $Update = new Update(
                (int) $Config->configArr['schema'],
                new Sql($sqlFs, $output),
                $Migrations,
            );
            $newSchema = $Update->runUpdateScript($input->getOption('force'));
            $output->writeln(sprintf('<info>Database is up to date (legacy schema %d).</info>', $newSchema));
        }
        return Command::SUCCESS;
    }
}
