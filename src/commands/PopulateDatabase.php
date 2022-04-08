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
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\ItemTypeParams;
use Elabftw\Elabftw\Sql;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Populate;
use function is_string;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use function mb_strlen;
use function str_repeat;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Populate the database with example data. Useful to get a fresh dev env.
 * For dev purposes, should not be used by normal users.
 */
class PopulateDatabase extends Command
{
    /** @var int DEFAULT_ITERATIONS number of things to create */
    private const DEFAULT_ITERATIONS = 50;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:populate';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Populate the database with fake data')
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
        try {
            $file = $input->getArgument('file');
            if (!is_string($file)) {
                throw new ImproperActionException('Could not read file from provided file path!');
            }
            $yaml = Yaml::parseFile($file);
        } catch (ParseException | ImproperActionException $e) {
            $output->writeln('Error parsing the file!');
            $output->writeln($e->getMessage());
            return 1;
        }

        // display header
        $output->writeln(array(
            $this->getDescription(),
            str_repeat('=', mb_strlen($this->getDescription())),
        ));

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


        // drop database
        $output->writeln('Dropping current database and loading structure');
        $this->dropAndInitDb();

        // adjust global config
        $configArr = $yaml['config'] ?? array();
        $configArr['smtp_password'] = $input->getOption('smtppass') ?? 'afakepassword';
        $configArr['smtp_username'] = $input->getOption('smtpuser') ?? 'somesmtpuser';
        $Config = Config::getConfig();
        $Config->updateAll($configArr);

        // create teams
        $Users = new Users();
        $Teams = new Teams($Users);
        foreach ($yaml['teams'] as $team) {
            $Teams->create(new ContentParams($team));
        }

        $iterations = $yaml['iterations'] ?? self::DEFAULT_ITERATIONS;
        $Populate = new Populate((int) $iterations);

        // create users
        // all users have the same password to make switching accounts easier
        // if the password is provided in the config file, it'll be used instead for that user
        foreach ($yaml['users'] as $user) {
            $Populate->createUser($Teams, $user);
        }

        // add more items types
        foreach ($yaml['items_types'] as $items_types) {
            $user = new Users();
            $user->team = (int) $items_types['team'];
            $ItemsTypes = new ItemsTypes($user);
            $extra = array(
                'color' => $items_types['color'],
                'body' => $items_types['template'],
                'canread' => 'team',
                'canwrite' => 'team',
                'isBookable' => $items_types['bookable'],
            );
            $ItemsTypes->create(new ItemTypeParams($items_types['name'], 'all', $extra));
        }


        // Insert an IDP
        $Idps = new Idps;
        $Idps->create('testidp', 'https://app.onelogin.com/', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', 'https://onelogin.com/', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', '-----BEGIN CERTIFICATE-----\r\nMIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEF\r\nBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYD\r\nVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAy\r\nOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMC\r\nVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJ\r\nZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG\r\n9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xA\r\nDhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTw\r\nYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsi\r\nWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjI\r\nhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1He\r\nhjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHc\r\nMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGc\r\nBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQG\r\nEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2lu\r\nIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06w\r\nCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA\r\nZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPf\r\nzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+Z\r\nZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHS\r\nurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8AB\r\nBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1\r\nc2Zk0y2rp0x3y3KG6K61Ug==\r\n-----END CERTIFICATE-----', '', '1', 'User.email', 'User.team', 'User.FirstName', 'User.LastName');

        $output->writeln('All done.');
        return 0;
    }

    private function dropAndInitDb(): void
    {
        $Db = Db::getConnection();
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        $Db->q('DROP database ' . DB_NAME);
        $Db->q('CREATE database ' . DB_NAME);
        $Db->q('USE ' . DB_NAME);

        // load structure
        $Sql->execFile('structure.sql');
    }
}
