<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\Update;
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
    protected function configure(): void
    {
        $this->setDescription('Generate a new database schema migration file')
            ->setHelp('This command allows you to generate a new schemaNNN.sql for database schema migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaNumber = Update::REQUIRED_SCHEMA + 1;
        $output->writeln(sprintf('Generating schema %d', $schemaNumber));
        $filePath = sprintf('%s/sql/schema%d.sql', dirname(__DIR__), $schemaNumber);
        $content = sprintf("-- schema %1\$s\n\nUPDATE config SET conf_value = %1\$s WHERE conf_name = 'schema';\n", $schemaNumber);
        file_put_contents($filePath, $content);
        $output->writeln('Created file: ' . $filePath);
        // now generate the down file
        $filePath = sprintf('%s/sql/schema%d-down.sql', dirname(__DIR__), $schemaNumber);
        $schemaNumberPrevious = $schemaNumber - 1;
        $content = sprintf("-- revert schema %d\n\nUPDATE config SET conf_value = %d WHERE conf_name = 'schema';\n", $schemaNumber, $schemaNumberPrevious);
        file_put_contents($filePath, $content);
        $output->writeln('Created file: ' . $filePath);
        return Command::SUCCESS;
    }
}
