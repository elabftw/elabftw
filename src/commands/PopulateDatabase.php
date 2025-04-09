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
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\Items;
use Elabftw\Models\Items2ExperimentsLinks;
use Elabftw\Models\Items2ItemsLinks;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Elabftw\Services\Populate;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Override;

use function array_key_exists;
use function is_string;
use function mb_strlen;
use function str_repeat;

/**
 * Populate the database with example data. Useful to get a fresh dev env.
 * For dev purposes, should not be used by normal users.
 */
#[AsCommand(name: 'db:populate')]
final class PopulateDatabase extends Command
{
    // number of things to create
    private const int DEFAULT_ITERATIONS = 50;

    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Populate the database with fake data')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation question')
            ->addArgument('file', InputArgument::REQUIRED, 'Yaml configuration file')
            ->setHelp('This command allows you to populate the database with fake users/experiments/items. The database will be dropped before populating it. The configuration is read from the yaml file passed as first argument.');
    }

    #[Override]
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
        $Config = Config::getConfig();
        $Config->patch(Action::Update, $yaml['config'] ?? array());

        $output->writeln('Creating teams, users, experiments, and resources...');
        // create teams
        $Users = new Users();
        $Teams = new Teams($Users);
        $Teams->bypassReadPermission = true;
        $Teams->bypassWritePermission = true;
        $Status = new ItemsStatus($Teams);
        $Category = new ExperimentsCategories($Teams);
        $faker = \Faker\Factory::create();

        foreach ($yaml['teams'] as $team) {
            $id = $Teams->postAction(Action::Create, array('name' => $team['name'], 'default_category_name' => $team['default_category_name'] ?? 'Lorem ipsum'));
            $Teams->setId($id);

            // create fake categories and status
            $Category->postAction(Action::Create, array('name' => 'Cell biology', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Project CRYPTO-COOL', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Project CASIMIR', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Tests', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Demo', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Discussions', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Production', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'R&D', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Support ticket', 'color' => $faker->hexColor(), 'is_default' => 0));

            $Status->postAction(Action::Create, array('name' => 'Maintenance mode', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Operational', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'In stock', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Need to reorder', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Destroyed', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Processed', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Waiting', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Open', 'color' => $faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Closed', 'color' => $faker->hexColor(), 'is_default' => 0));

            $columns = array(
                'visible',
                'onboarding_email_body',
                'onboarding_email_subject',
                'onboarding_email_active',
            );
            foreach ($columns as $column) {
                if (isset($team[$column])) {
                    $Teams->patch(Action::Update, array($column => (string) $team[$column]));
                }
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

        // create predefined templates
        if (isset($yaml['templates'])) {
            foreach ($yaml['templates'] as $template) {
                $user = new Users((int) ($template['user'] ?? 1), (int) ($template['team'] ?? 1));
                $Templates = new Templates($user);
                $id = $Templates->postAction(Action::Create, array());
                $Templates->setId($id);
                $patch = array(
                    'title' => $template['title'],
                    'body' => $template['body'] ?? '',
                    'category' => $template['category'] ?? 2,
                    'status' => $template['status'] ?? 2,
                    'metadata' => $template['metadata'] ?? '{}',
                );
                $Templates->patch(Action::Update, $patch);
                if (isset($template['locked'])) {
                    $Templates->toggleLock();
                }
                if (isset($template['tags'])) {
                    foreach ($template['tags'] as $tag) {
                        $Templates->Tags->postAction(Action::Create, array('tag' => $tag));
                    }
                }
                if (isset($template['items_links'])) {
                    foreach ($template['items_links'] as $target) {
                        $Templates->ItemsLinks->setId($target);
                        $Templates->ItemsLinks->postAction(Action::Create, array());
                    }
                }
            }
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
                    'body' => $experiment['body'] ?? '',
                    'date' => $experiment['date'],
                    'category' => $experiment['category'] ?? 2,
                    'status' => $experiment['status'] ?? 2,
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

        // add Resources Categories (items types)
        if (array_key_exists('items_types', $yaml)) {
            foreach ($yaml['items_types'] as $items_types) {
                $user = new Users(1, (int) ($items_types['team'] ?? 1));
                $ItemsTypes = new ItemsTypes($user);
                $ItemsTypes->setId($ItemsTypes->create(title: $items_types['name']));
                $ItemsTypes->bypassWritePermission = true;
                $defaultPermissions = BasePermissions::Team->toJson();
                $patch = array(
                    'color' => $items_types['color'],
                    'body' => $items_types['template'] ?? '',
                    'canread' => $defaultPermissions,
                    'canwrite' => $defaultPermissions,
                    'metadata' => $items_types['metadata'] ?? '{}',
                );
                $ItemsTypes->patch(Action::Update, $patch);
            }
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
                        $ExperimentsLinks = new Items2ExperimentsLinks($Items, (int) $target);
                        $ExperimentsLinks->postAction(Action::Create, array());
                    }
                }
                if (isset($item['items_links'])) {
                    foreach ($item['items_links'] as $target) {
                        $ItemsLinks = new Items2ItemsLinks($Items, (int) $target);
                        $ItemsLinks->postAction(Action::Create, array());
                    }
                }
                $Items->patch(Action::Update, $patch);
            }
        }

        $output->writeln('All done.');
        return Command::SUCCESS;
    }

    private function dropAndInitDb(): void
    {
        $Db = Db::getConnection();
        $Db->q('DROP database ' . Config::fromEnv('DB_NAME'));
        $Db->q('CREATE database ' . Config::fromEnv('DB_NAME'));
        $Db->q('USE ' . Config::fromEnv('DB_NAME'));

        // load structure
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        $Sql->execFile('structure.sql');
    }
}
