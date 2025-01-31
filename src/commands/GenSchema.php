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

use Elabftw\Elabftw\Update;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * For dev purposes: generate a new empty schema file
 */
#[AsCommand(name: 'dev:genschema')]
class GenSchema extends Command
{
    public function __construct(private FilesystemOperator $fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generate a new database schema migration file')
            ->setHelp('This command allows you to generate a new schemaNNN.sql for database schema migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaNumber = Update::REQUIRED_SCHEMA + 1;
        $output->writeln(sprintf('Generating schema %d', $schemaNumber));
        $filename = sprintf('schema%d.sql', $schemaNumber);
        $content = sprintf("-- schema %d\n\n", $schemaNumber);
        $this->fs->write($filename, $content);
        $output->writeln('Created file: ' . $filename);
        // now generate the down file
        $filename = sprintf('schema%d-down.sql', $schemaNumber);
        $schemaNumberPrevious = $schemaNumber - 1;
        $content = sprintf("-- revert schema %d\n\nUPDATE config SET conf_value = %d WHERE conf_name = 'schema';\n", $schemaNumber, $schemaNumberPrevious);
        $this->fs->write($filename, $content);
        $output->writeln('Created file: ' . $filename);
        return Command::SUCCESS;
    }
}
