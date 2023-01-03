<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Enums\Action;
use Elabftw\Models\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * For dev purposes: force the schema to a particular version
 */
class ForceSchema extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:forceschema';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Generate a new database schema migration file')
            ->addArgument('schema', InputArgument::REQUIRED, 'Target schema number')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to generate a new schemaNNN.sql for database schema migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $Config = Config::getConfig();
        $schemaNumber = $input->getArgument('schema');
        $Config->patch(Action::Update, array('schema' => $schemaNumber));
        $output->writeln(sprintf('Changing schema to %d', $schemaNumber));
        return 0;
    }
}
