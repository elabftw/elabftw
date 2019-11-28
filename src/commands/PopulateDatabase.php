<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Sql;
use Elabftw\Models\Config;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Populate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Populate the database with example data. Useful to get a fresh dev env.
 * For dev purposes, should not be used by normal users.
 */
class PopulateDatabase extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:populate';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Populate the database with fake data')
            // if options are not provided they will be asked for
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Users password (8 chars min)')
            ->addOption('mailfrom', 'm', InputOption::VALUE_REQUIRED, 'Email address for From: field')
            ->addOption('smtpuser', 'u', InputOption::VALUE_REQUIRED, 'SMTP username')
            ->addOption('smtppass', 's', InputOption::VALUE_REQUIRED, 'SMTP password')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to populate the database with fake users/experiments/items. The database will be dropped before populating it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            $this->getDescription(),
            \str_repeat('=', \mb_strlen($this->getDescription())),
        ));
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("WARNING: this command will completely ERASE your current database!\nAre you sure you want to continue? (y/n)\n", false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Aborting!');
            return;
        }

        // all users have the same password to make switching accounts easier
        if ($input->getOption('password')) {
            $password = $input->getOption('password');
        } else {
            $question = new Question('Password for users (8 chars min): ', 'password');
            $password = $helper->ask($input, $output, $question);
        }

        if ($input->getOption('mailfrom')) {
            $mailfrom = $input->getOption('mailfrom');
        } else {
            $question = new Question('Mail from: ', 'elabadmin@example.com');
            $mailfrom = $helper->ask($input, $output, $question);
        }

        if ($input->getOption('smtpuser')) {
            $smtpuser = $input->getOption('smtpuser');
        } else {
            $question = new Question('SMTP user: ', 'user');
            $smtpuser = $helper->ask($input, $output, $question);
        }

        if ($input->getOption('smtppass')) {
            $smtppass = $input->getOption('smtppass');
        } else {
            $question = new Question('SMTP password: ', 'secr3t');
            $smtppass = $helper->ask($input, $output, $question);
        }

        $Db = Db::getConnection();
        $Sql = new Sql();
        $Faker = \Faker\Factory::create();
        $Populate = new Populate();

        // drop database
        $output->writeln('Dropping current database');
        $Db->q('DROP database ' . \DB_NAME);
        $Db->q('CREATE database ' . \DB_NAME);
        $Db->q('USE ' . \DB_NAME);

        // load structure
        $output->writeln('Loading structure');
        $Sql->execFile('structure.sql');

        // adjust global config
        $configArr = array(
            'admin_validate' => 0,
            'debug' => 1,
            'mail_from' => $mailfrom,
            'smtp_password' => $smtppass,
            'smtp_username' => $smtpuser,
            'url' => 'https://elab.local:3148',
        );
        $Config = new Config();
        $Config->update($configArr);

        // create the teams
        $Users = new Users();
        $Teams = new Teams($Users);
        $Teams->create('Alpha team');
        $Teams->create('Bravo squad');
        $Teams->create('Tango');

        // Alpha team
        // toto is the sysadmin and admin of team α
        $Users->create('toto@yopmail.com', 1, 'Toto', 'Le houf', $password);
        // add more items types
        $ItemsTypes = new ItemsTypes(new Users(1));
        $ItemsTypes->update(1, 'Molecule', '#29AEB9', 0, '');
        $ItemsTypes->create('Microscope', '#54AA08', 1, 'Objectives:', 1);
        $ItemsTypes->create('Plasmid', '#C0C0C0', 0, 'Concentration:', 1);
        $ItemsTypes->create('Antibody', '#C24F3D', 0, 'Host:', 1);

        // create experiments and items
        $Experiments = new Experiments(new Users(1));
        $Database = new Database(new Users(1));
        $Populate->generate($Experiments);
        $Populate->generate($Database);

        // titi is a user in the team of toto
        $Users->create('titi@yopmail.com', 1, 'Titi', $Faker->lastName, $password);
        $Experiments = new Experiments(new Users(2));
        $Populate->generate($Experiments, 50);
        // tutu is another user
        $Users->create('tutu@yopmail.com', 1, $Faker->firstName, $Faker->lastName, $password);

        // Bravo team
        // tata is the admin of bravo team
        $Users->create('tata@yopmail.com', 2, 'Tata', $Faker->lastName, $password);
        $Users->create('tyty@yopmail.com', 2, $Faker->firstName, $Faker->lastName, $password);
        $Users->create('aaaaa@yopmail.com', 2, $Faker->firstName, $Faker->lastName, $password);

        // Charlie team
        $Users->create('tete@yopmail.com', 3, 'Tete', 'Gamma', $password);
        $Users->create('bbbbb@yopmail.com', 3, $Faker->firstName, $Faker->lastName, $password);

        $output->writeln('All done.');
    }
}
