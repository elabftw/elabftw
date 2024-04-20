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

use Elabftw\Elabftw\Db;

use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Models\Config;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function dirname;

/**
 * Import database structure
 */
#[AsCommand(name: 'db:install')]
class Install extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Install the MySQL structure for eLabFTW in a MySQL database')
            ->setHelp('This command will initialize the MySQL database with the correct tables.')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Delete and recreate the database before installing the structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Db = Db::getConnection();

        $req = $Db->q('SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = "' . Config::fromEnv('DB_NAME') . '"');
        $res = $req->fetch();
        if ((int) $res['cnt'] > 1 && !$input->getOption('reset')) {
            $output->writeln('<info>→ Database structure already present. Skipping initialization.</info>');
            return Command::SUCCESS;
        }

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
            '',
        ));

        if ($input->getOption('reset')) {
            $output->writeln('<info>→ Resetting MySQL database...</info>');
            $Db->q('DROP DATABASE ' . Config::fromEnv('DB_NAME'));
            $Db->q('CREATE DATABASE ' . Config::fromEnv('DB_NAME') . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
            $Db->q('USE ' . Config::fromEnv('DB_NAME'));
        }

        $output->writeln('<info>→ Initializing MySQL database...</info>');
        $sqlFs = FsTools::getFs(dirname(__DIR__) . '/sql');
        (new Sql($sqlFs))->execFile('structure.sql');
        // now create the default team
        $Teams = new Teams(new Users());
        $Teams->bypassWritePermission = true;
        $Teams->postAction(Action::Create, array('name' => 'Default team'));
        $output->writeln('<info>✓ Installation successful! You can now start using your eLabFTW instance.</info>');
        $output->writeln('<info>→ Register your Sysadmin account here: ' . Config::fromEnv('SITE_URL') . '/register.php</info>');
        $output->writeln('<info>→ Subscribe to the low volume newsletter to stay informed about new releases: http://eepurl.com/bTjcMj</info>');
        return Command::SUCCESS;
    }
}
