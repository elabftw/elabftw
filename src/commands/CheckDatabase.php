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

use Elabftw\Elabftw\Sql;
use Elabftw\Elabftw\Update;
use Elabftw\Models\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check the the current schema version versus the required one
 */
class CheckDatabase extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'db:check';

    /**
     * Set the help messages
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Check the database version')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to compare the database version with the current required schema.');
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if no need to upgrade, 1 if need to upgrade
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Config = new Config();
        $Update = new Update($Config, new Sql());

        $current = (int) $Config->configArr['schema'];
        $required = $Update->getRequiredSchema();

        $output->writeln(array(
            'Database check',
            '==============',
            'Current version: ' . (string) $current,
            'Required version: ' . (string) $required,
        ));
        if ($current === $required) {
            $output->writeln('No upgrade required.');
            return 0;
        }

        $output->writeln('An upgrade is required.');
        return 1;
    }
}
