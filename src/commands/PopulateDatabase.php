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
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Elabftw\Sql;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Idps;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\Populate;
use function is_string;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Populate the database with example data. Useful to get a fresh dev env.
 * For dev purposes, should not be used by normal users.
 */
class PopulateDatabase extends Command
{
    /** @var string DEFAULT_PASSWORD the password to use if none are provided */
    private const DEFAULT_PASSWORD = 'totototo';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:populate';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Populate the database with fake data')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Users password (8 chars min)')
            ->addOption('smtpuser', 'u', InputOption::VALUE_REQUIRED, 'SMTP username')
            ->addOption('smtppass', 's', InputOption::VALUE_REQUIRED, 'SMTP password')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation question')
            ->addArgument('file', InputArgument::REQUIRED, 'Yaml configuration file')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to populate the database with fake users/experiments/items. The database will be dropped before populating it. The configuration is read from the yaml file passed as first argument.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // display header
        $output->writeln(array(
            $this->getDescription(),
            \str_repeat('=', \mb_strlen($this->getDescription())),
        ));

        // read the yaml config file
        $file = $input->getArgument('file');
        if (!is_string($file)) {
            $output->writeln('Error parsing the file path!');
            return 1;
        }
        try {
            $yaml = Yaml::parseFile($file);
        } catch (ParseException $e) {
            $output->writeln('Error parsing the file!');
            $output->writeln($e->getMessage());
            return 1;
        }

        // ask confirmation before deleting all the database
        $helper = $this->getHelper('question');
        // the -y flag overrides the config value
        if ($yaml['skip_confirm'] === false && !$input->getOption('yes')) {
            $question = new ConfirmationQuestion("WARNING: this command will completely ERASE your current database!\nAre you sure you want to continue? (y/n)\n", false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting!');
                return 1;
            }
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
            'mail_from' => $yaml['mailfrom'],
            'smtp_password' => $input->getOption('smtppass') ?? 'afakepassword',
            'smtp_username' => $input->getOption('smtpuser') ?? 'somesmtpuser',
            'url' => $yaml['url'],
        );
        $Config = new Config();
        $Config->update($configArr);

        // create teams
        $Users = new Users();
        $Teams = new Teams($Users);
        foreach ($yaml['teams'] as $team) {
            $Teams->create($team);
        }

        $Request = Request::createFromGlobals();
        $Session = new Session();
        $Request->setSession($Session);


        $iterations = $yaml['iterations'] ?? 50;

        // create users
        // all users have the same password to make switching accounts easier
        // if the password is provided in the config file, it'll be used instead for that user
        foreach ($yaml['users'] as $user) {
            $firstname = $user['firstname'] ?? $Faker->firstName;
            $lastname = $user['lastname'] ?? $Faker->lastName;
            $password = $user['password'] ?? $input->getOption('password') ?? self::DEFAULT_PASSWORD;
            if (!is_string($password)) {
                $password = self::DEFAULT_PASSWORD;
            }
            $email = $user['email'] ?? $Faker->safeEmail;

            $userid = $Users->create($email, array($user['team']), $firstname, $lastname, $password, null, true, true, false);
            $team = $Teams->getTeamsFromIdOrNameOrOrgidArray(array($user['team']));
            $Users = new Users($userid, (int) $team[0]['id']);

            if ($user['create_mfa_secret'] ?? false) {
                $MfaHelper = new MfaHelper($userid);
                // use a fixed secret
                $MfaHelper->secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
                $MfaHelper->saveSecret();
            }
            if ($user['create_experiments'] ?? false) {
                $Populate->generate(new Experiments($Users), $iterations);
            }
            if ($user['create_items'] ?? false) {
                $Populate->generate(new Database($Users), $iterations);
            }
            if ($user['api_key'] ?? false) {
                $ApiKeys = new ApiKeys($Users);
                $ApiKeys->createKnown($user['api_key']);
            }

            if ($user['create_templates'] ?? false) {
                $Templates = new Templates($Users);
                for ($i = 0; $i < 100; $i++) {
                    $Templates->create(new ParamsProcessor(
                        array('name' => $Faker->sentence, 'template' => $Faker->realText(1000))
                    ));
                }
            }
        }

        // add more items types
        $Users1 = new Users(1, 1);
        $ItemsTypes = new ItemsTypes($Users1);
        foreach ($yaml['items_types'] as $items_types) {
            $ItemsTypes->create(
                new ParamsProcessor(
                    array(
                        'name' => $items_types['name'],
                        'color' => $items_types['color'],
                        'bookable' => (int) $items_types['bookable'],
                        'template' => $items_types['template'],
                    )
                ),
                $items_types['team']
            );
        }


        // Insert an IDP
        $Idps = new Idps;
        $Idps->create('testidp', 'https://app.onelogin.com/', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', '-----BEGIN CERTIFICATE-----\r\nMIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEF\r\nBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYD\r\nVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAy\r\nOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMC\r\nVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJ\r\nZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG\r\n9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xA\r\nDhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTw\r\nYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsi\r\nWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjI\r\nhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1He\r\nhjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHc\r\nMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGc\r\nBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQG\r\nEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2lu\r\nIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06w\r\nCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA\r\nZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPf\r\nzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+Z\r\nZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHS\r\nurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8AB\r\nBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1\r\nc2Zk0y2rp0x3y3KG6K61Ug==\r\n-----END CERTIFICATE-----', '1');

        $output->writeln('All done.');
        return 0;
    }
}
