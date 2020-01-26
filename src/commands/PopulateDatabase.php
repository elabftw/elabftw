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
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Idps;
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
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation question')

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
        // ask question before populating unless --yes is provided
        if (!$input->getOption('yes')) {
            $question = new ConfirmationQuestion("WARNING: this command will completely ERASE your current database!\nAre you sure you want to continue? (y/n)\n", false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting!');
                return 1;
            }
        }

        // all users have the same password to make switching accounts easier
        if ($input->getOption('password')) {
            $password = $input->getOption('password');
        } else {
            $question = new Question('Password for users (8 chars min): ', 'password');
            $password = $helper->ask($input, $output, $question);
        }
        $password = (string) $password;

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
        $Users->create('toto@yopmail.com', array('Alpha team'), 'Toto', 'Le houf', $password);
        // add a known API key for this user so we can test it reproducibly
        $Users1 = new Users(1, 1);
        $ApiKeys = new ApiKeys($Users1);
        $ApiKeys->createKnown();
        // add more items types
        $ItemsTypes = new ItemsTypes($Users1);
        $ItemsTypes->update(1, 'Molecule', '#29AEB9', 0, '');
        $ItemsTypes->create('Microscope', '#54AA08', 1, 'Objectives:', 1);
        $ItemsTypes->create('Plasmid', '#C0C0C0', 0, 'Concentration:', 1);
        $ItemsTypes->create('Antibody', '#C24F3D', 0, 'Host:', 1);

        // create experiments and items
        $Experiments = new Experiments($Users1);
        $Database = new Database($Users1);
        $Populate->generate($Experiments);
        $Populate->generate($Database);

        // titi is a user in the team of toto
        $Users->create('titi@yopmail.com', array('Alpha team'), 'Titi', $Faker->lastName, $password);
        $Experiments = new Experiments(new Users(2, 1));
        $Populate->generate($Experiments, 50);
        // tutu is another user
        $Users->create('tutu@yopmail.com', array('Alpha team'), $Faker->firstName, $Faker->lastName, $password);

        // Bravo team
        // tata is the admin of bravo team
        $Users->create('tata@yopmail.com', array('Bravo squad'), 'Tata', $Faker->lastName, $password);
        $Users->create('tyty@yopmail.com', array('Bravo squad'), $Faker->firstName, $Faker->lastName, $password);
        $Users->create('aaaaa@yopmail.com', array('Bravo squad'), $Faker->firstName, $Faker->lastName, $password);

        // Charlie team
        $Users->create('tete@yopmail.com', array('Tango'), 'Tete', 'Gamma', $password);
        $Users->create('bbbbb@yopmail.com', array('Tango'), $Faker->firstName, $Faker->lastName, $password);

        // Insert an IDP
        $Idps = new Idps;
        $Idps->create('testidp', 'https://app.onelogin.com/', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', '-----BEGIN CERTIFICATE-----\r\nMIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEF\r\nBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYD\r\nVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAy\r\nOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMC\r\nVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJ\r\nZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG\r\n9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xA\r\nDhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTw\r\nYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsi\r\nWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjI\r\nhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1He\r\nhjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHc\r\nMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGc\r\nBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQG\r\nEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2lu\r\nIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06w\r\nCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA\r\nZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPf\r\nzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+Z\r\nZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHS\r\nurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8AB\r\nBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1\r\nc2Zk0y2rp0x3y3KG6K61Ug==\r\n-----END CERTIFICATE-----', '1');



        $output->writeln('All done.');
        return 0;
    }
}
