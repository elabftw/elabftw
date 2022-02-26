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

use const DB_NAME;
use function dirname;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Sql;
use Elabftw\Services\DatabaseInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import database structure
 */
class Install extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'start';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Install eLabFTW in a MySQL database')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Ask information to connect to the MySQL database, create the config file and load the database structure.')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Delete and recreate the database before installing the structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(array(
            '',
            '      _          _     _____ _______        __',
            "  ___| |    __ _| |__ |  ___|_   _\ \      / /",
            " / _ \ |   / _| | '_ \| |_    | |  \ \ /\ / / ",
            "|  __/ |__| (_| | |_) |  _|   | |   \ V  V /  ",
            " \___|_____\__,_|_.__/|_|     |_|    \_/\_/   ",
            '                                              ',
            '',
        ));

        $output->writeln(array(
            '<info>Welcome to the install of eLabFTW!</info>',
            '<info>This program will install the MySQL structure.</info>',
            '<info>Before proceeding, make sure you have an empty MySQL database for eLabFTW with a user+password to access it.</info>',
            '',
        ));

        $output->writeln('<info>→ Reading file config.php...</info>');
        require_once dirname(__DIR__, 2) . '/config.php';

        if ($input->getOption('reset')) {
            $output->writeln('<info>→ Resetting MySQL database...</info>');
            $Db = Db::getConnection();
            $Db->q('DROP DATABASE ' . DB_NAME);
            $Db->q('CREATE DATABASE ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
            $Db->q('USE ' . DB_NAME);
        }

        $output->writeln('<info>→ Initializing MySQL database...</info>');
        $sqlFs = FsTools::getFs(dirname(__DIR__) . '/sql');
        $Installer = new DatabaseInstaller(new Sql($sqlFs));
        $Installer->install();
        $output->writeln('<info>✓ Installation successful! You can now start using your eLabFTW instance.</info>');
        $output->writeln('<info>→ Subscribe to the low volume newsletter to stay informed about new releases: http://eepurl.com/bTjcMj</info>');
        return 0;
    }
}
