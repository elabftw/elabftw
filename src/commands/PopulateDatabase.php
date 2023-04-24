<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsLinks;
use Elabftw\Models\Idps;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsLinks;
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
            $output->writeln(sprintf('Error parsing the file: %s', $e->getMessage()));
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

            /** @phpstan-ignore-next-line ask method is part of QuestionHelper which extends HelperInterface */
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting!');
                return 1;
            }
        }


        // drop database
        $output->writeln('Dropping current database and loading structure...');
        $this->dropAndInitDb();

        // adjust global config
        $configArr = $yaml['config'] ?? array();
        $Config = Config::getConfig();
        $Config->patch(Action::Update, $configArr);

        // create teams
        $Users = new Users();
        $Teams = new Teams($Users);
        $Teams->bypassReadPermission = true;
        $Teams->bypassWritePermission = true;
        foreach ($yaml['teams'] as $team) {
            $id = $Teams->postAction(Action::Create, array('name' => $team['name'], 'default_category_name' => $team['default_category_name'] ?? 'Lorem ipsum'));
            if (isset($team['visible'])) {
                $Teams->setId($id);
                $Teams->patch(Action::Update, array('visible' => (string) $team['visible']));
            }
        }

        $iterations = $yaml['iterations'] ?? self::DEFAULT_ITERATIONS;
        $Populate = new Populate((int) $iterations);

        // create users
        // all users have the same password to make switching accounts easier
        // if the password is provided in the config file, it'll be used instead for that user
        foreach ($yaml['users'] as $user) {
            $Populate->createUser($Teams, $user);
        }

        if (isset($yaml['experiments'])) {
            // read defined experiments
            foreach ($yaml['experiments'] as $experiment) {
                $user = new Users((int) ($experiment['user'] ?? 1), (int) ($experiment['team'] ?? 1));
                $Experiments = new Experiments($user);
                $id = $Experiments->postAction(Action::Create, array());
                $Experiments->setId($id);
                $patch = array(
                    'title' => $experiment['title'],
                    'body' => $experiment['body'],
                    'date' => $experiment['date'],
                    'category' => $experiment['status'] ?? 2,
                    'metadata' => $experiment['metadata'] ?? '{}',
                    'rating' => $experiment['rating'] ?? 0,
                );
                $Experiments->patch(Action::Update, $patch);
                if (isset($experiment['locked'])) {
                    $Experiments->toggleLock();
                }
                if (isset($experiment['tags'])) {
                    foreach ($experiment['tags'] as $tag) {
                        $Experiments->Tags->postAction(Action::Create, array('tag' => $tag));
                    }
                }
                if (isset($experiment['comments'])) {
                    foreach ($experiment['comments'] as $comment) {
                        $Experiments->Comments->postAction(Action::Create, array('comment' => $comment));
                    }
                }
                if (isset($experiment['experiments_links'])) {
                    foreach ($experiment['experiments_links'] as $target) {
                        $Experiments->ExperimentsLinks->setId($target);
                        $Experiments->ExperimentsLinks->postAction(Action::Create, array());
                    }
                }
                if (isset($experiment['items_links'])) {
                    foreach ($experiment['items_links'] as $target) {
                        $Experiments->ItemsLinks->setId($target);
                        $Experiments->ItemsLinks->postAction(Action::Create, array());
                    }
                }
            }
        }

        // delete the default items_types
        // add more items types
        foreach ($yaml['items_types'] as $items_types) {
            $user = new Users(1, (int) ($items_types['team'] ?? 1));
            $ItemsTypes = new ItemsTypes($user);
            $ItemsTypes->setId($ItemsTypes->create($items_types['name']));
            $ItemsTypes->bypassWritePermission = true;
            $defaultPermissions = BasePermissions::MyTeams->toJson();
            $patch = array(
                'color' => $items_types['color'],
                'body' => $items_types['template'] ?? '',
                'canread' => $defaultPermissions,
                'canwrite' => $defaultPermissions,
                'bookable' => $items_types['bookable'] ?? false,
                'metadata' => $items_types['metadata'] ?? '{}',
            );
            $ItemsTypes->patch(Action::Update, $patch);
        }

        // randomize the entries so they look like they are not added at once
        if (isset($yaml['items'])) {
            shuffle($yaml['items']);
            foreach ($yaml['items'] as $item) {
                $user = new Users((int) ($item['user'] ?? 1), (int) ($item['team'] ?? 1));
                $Items = new Items($user);
                $id = $Items->postAction(Action::Create, array('category_id' => $item['category']));
                $Items->setId($id);
                $patch = array(
                    'title' => $item['title'],
                    'body' => $item['body'] ?? '',
                    'date' => $item['date'] ?? date('Ymd'),
                    'rating' => $item['rating'] ?? 0,
                );
                // don't override the items type metadata
                if (isset($item['metadata'])) {
                    $patch['metadata'] = $item['metadata'];
                }
                if (isset($item['experiments_links'])) {
                    foreach ($item['experiments_links'] as $target) {
                        $ExperimentsLinks = new ExperimentsLinks($Items, (int) $target);
                        $ExperimentsLinks->postAction(Action::Create, array());
                    }
                }
                if (isset($item['items_links'])) {
                    foreach ($item['items_links'] as $target) {
                        $ExperimentsLinks = new ItemsLinks($Items, (int) $target);
                        $ExperimentsLinks->postAction(Action::Create, array());
                    }
                }
                $Items->patch(Action::Update, $patch);
            }
        }

        // Insert an IDP
        $Idps = new Idps();
        $Idps->postAction(Action::Create, array(
            'name' => 'testidp',
            'entityid' => 'https://app.onelogin.com/',
            'sso_url' => 'https://onelogin.com/',
            'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'slo_url' => 'https://onelogin.com/',
            'slo_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'x509' => '-----BEGIN CERTIFICATE-----MIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEFBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xADhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTwYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsiWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjIhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1Hehjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHcMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGcBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQGEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06wCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPfzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+ZZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHSurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8ABBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1c2Zk0y2rp0x3y3KG6K61Ug==-----END CERTIFICATE-----',
            'x509_new' => '',
            'email_attr' => 'User.email',
            'team_attr' => 'User.team',
            'fname_attr' => 'User.FirstName',
            'lname_attr' => 'User.LastName',
            'orgid_attr' => 'internal_id',
        ));

        $output->writeln('All done.');
        return 0;
    }

    private function dropAndInitDb(): void
    {
        $Db = Db::getConnection();
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        $Db->q('DROP database ' . Config::fromEnv('DB_NAME'));
        $Db->q('CREATE database ' . Config::fromEnv('DB_NAME'));
        $Db->q('USE ' . Config::fromEnv('DB_NAME'));

        // load structure
        $Sql->execFile('structure.sql');
    }
}
