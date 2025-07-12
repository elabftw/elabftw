<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\FileFromString;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\Items2ExperimentsLinks;
use Elabftw\Models\Items2ItemsLinks;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Tags;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\UltraAdmin;
use Elabftw\Models\Users;
use Elabftw\Params\UserParams;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is used to generate data for dev purposes
 */
final class Populate
{
    // the password to use if none are provided
    private const string DEFAULT_PASSWORD = 'totototototo';

    // number of things to create
    private const int DEFAULT_ITERATIONS = 50;

    // number of templates to generate
    private const int TEMPLATES_ITER = 5;

    private int $iter;

    private \Faker\Generator $faker;

    // iter: iterations: number of things to generate
    public function __construct(private OutputInterface $output, private array $yaml, private bool $fast = false)
    {
        $this->faker = \Faker\Factory::create();
        $this->iter = $this->fast ? 2 : $yaml['iterations'] ?? self::DEFAULT_ITERATIONS;
    }

    public function run(): void
    {
        // drop database
        $this->output->writeln('▶ Dropping current database and loading structure...');
        $this->dropAndInitDb();

        // adjust global config
        Config::getConfig()->patch(Action::Update, $this->yaml['config'] ?? array());

        $this->output->writeln('┌ Creating teams, users, experiments, and resources...');
        $Users = new UltraAdmin(1, 1);
        $Teams = new Teams($Users, bypassWritePermission: true);
        $Status = new ItemsStatus($Teams);
        $Category = new ExperimentsCategories($Teams);
        foreach ($this->yaml['teams'] as $team) {
            $teamid = $Teams->create($team['name']);
            $this->output->writeln(sprintf('├ + team: %s (ID: %d)', $team['name'], $teamid));
            $Teams->setId($teamid);

            // create fake categories and status
            $Category->postAction(Action::Create, array('name' => 'Cell biology', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Project CRYPTO-COOL', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Project CASIMIR', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Tests', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Demo', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Discussions', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Production', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'R&D', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Category->postAction(Action::Create, array('name' => 'Support ticket', 'color' => $this->faker->hexColor(), 'is_default' => 0));

            $Status->postAction(Action::Create, array('name' => 'Maintenance mode', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Operational', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'In stock', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Need to reorder', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Destroyed', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Processed', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Waiting', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Open', 'color' => $this->faker->hexColor(), 'is_default' => 0));
            $Status->postAction(Action::Create, array('name' => 'Closed', 'color' => $this->faker->hexColor(), 'is_default' => 0));

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
            // create users inside that team
            // all users have the same password to make switching accounts easier
            // if the password is provided in the config file, it'll be used instead for that user
            foreach ($team['users'] ?? array() as $user) {
                $this->createUser($teamid, $user);
            }
            if (array_key_exists('random_users', $team) && !$this->fast) {
                $iter = (int) $team['random_users'];
                for ($i = 0; $i < $iter; $i++) {
                    $this->createUser($teamid);
                }
            }

            // EXPERIMENTS TEMPLATES
            foreach ($team['templates'] ?? array() as $template) {
                $user = new Users((int) ($template['user'] ?? 1), $teamid);
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

            // EXPERIMENTS
            foreach ($team['experiments'] ?? array() as $experiment) {
                $user = new Users((int) ($experiment['user'] ?? 1), $teamid);
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
                $this->output->writeln(sprintf('├ + experiment: %s (id: %d in team: %d)', $experiment['title'], $id, $teamid));
            }

            // add Resources Categories (items types)
            foreach ($team['items_types'] ?? array() as $items_types) {
                $ItemsTypes = new ItemsTypes(new Users(1, $teamid));
                $defaultPermissions = BasePermissions::Team->toJson();
                $itemTypeId = $ItemsTypes->create(
                    title: $items_types['name'],
                    color: $items_types['color'],
                    body: $items_types['template'] ?? '',
                    date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                    canread: $defaultPermissions,
                    canwrite: $defaultPermissions,
                    metadata: $items_types['metadata'] ?? '{}',
                );
                $this->output->writeln(sprintf('├ + resource category: %s (id: %d in team: %d)', $items_types['name'], $itemTypeId, $teamid));
            }

            // randomize the entries so they look like they are not added at once
            if (isset($team['items'])) {
                shuffle($team['items']);
                foreach ($team['items'] as $item) {
                    $user = new Users((int) ($item['user'] ?? 1), $teamid);
                    $ItemsTypes = new ItemsTypes($user);
                    $Items = new Items($user);
                    $id = $Items->create(
                        template: (int) ($item['category'] ?? $ItemsTypes->getDefault()),
                        title: $item['title'],
                        body: $item['body'] ?? '',
                        date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                        rating: $item['rating'] ?? 0,
                    );
                    $Items->setId($id);
                    $patch = array();
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
                    $this->output->writeln(sprintf('├ + resource: %s (id: %d in team: %d)', $item['title'], $id, $teamid));
                }
            }
        }

        // COMPOUNDS
        $mock = new MockHandler(array(
            new Response(200, array(), 'nothing'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, $Users, new NullFingerprinter());
        foreach ($this->yaml['compounds'] ?? array() as $compound) {
            $id = $Compounds->create(
                name: $compound['name'],
                molecularFormula: $compound['molecular_formula'],
                casNumber: $compound['cas_number'],
                inchi: $compound['inchi'],
                inchiKey: $compound['inchi_key'],
                iupacName: $compound['iupac_name'],
                molecularWeight: (float) $compound['molecular_weight'],
                pubchemCid: (int) $compound['pubchem_cid'],
                smiles: $compound['smiles'],
            );
            $this->output->writeln(sprintf('├ + compound: %s (ID: %d)', $compound['name'], $id));
        }
    }

    /**
     * Populate the db with fake experiments or items
     */
    public function generate(Experiments | Items $Entity, ?int $iterations = null): void
    {
        $iterations ??= $this->iter;
        $Teams = new Teams($Entity->Users, $Entity->Users->team, bypassWritePermission: true);
        if ($Entity instanceof Experiments) {
            $Category = new ExperimentsCategories($Teams);
            $Status = new ExperimentsStatus($Teams);
            $tpl = 0;
        } else {
            $Category = new ItemsTypes($Entity->Users, bypassReadPermission: true, bypassWritePermission: true);
            if (empty($Category->readAll())) {
                $Category->create();
            }
            $Status = new ItemsStatus($Teams);
            $tpl = (int) $Category->readAll()[0]['id'];
        }
        $categoryArr = $Category->readAll();
        $statusArr = $Status->readAll();

        // we will randomly pick from these for canread and canwrite
        $visibilityArr = array(
            BasePermissions::Full->toJson(),
            BasePermissions::Organization->toJson(),
            BasePermissions::Team->toJson(),
            BasePermissions::User->toJson(),
            BasePermissions::UserOnly->toJson(),
        );

        $tagsArr = array(
            'Project X',
            'collaboration',
            'SCP-2702',
            'Western Blot',
            'HeLa',
            'Fly',
            'Dark Arts',
            'COVID-24',
            'FLIM',
            'FRET',
            'Open Source',
            'Software',
            'Secret',
            'Copper',
            'nanotechnology',
            'spectroscopy',
            'hardness testing',
            'cell culture',
            'DNA sequencing',
            'PCR',
            'gene expression',
            'protein purification',
            'biological samples',
            'data analysis',
            'lab safety',
            'genetics',
            'molecular biology',
            'cell biology',
            'biotechnology',
            'biochemistry',
            'microbiology',
            'ecology',
            'bioinformatics',
            'research methodology',
            'lab techniques',
            'experimental design',
            'ethics in research',
            'laboratory management',
            'scientific collaboration',
            'lab supplies',
            'scientific discovery',
            'data interpretation',
            'hypothesis testing',
            'cell culture techniques',
            'genomic analysis',
            'protein analysis',
            'molecular cloning',
            'biomolecular assays',
            'statistical analysis',
            'scientific literature',
        );

        for ($i = 0; $i <= $iterations; $i++) {
            $id = $Entity->create(template: $tpl);
            $Entity->setId($id);
            // variable tag number
            $Tags = new Tags($Entity);
            $tagNb = $this->faker->numberBetween(0, 5);
            for ($j = 0; $j <= $tagNb; $j++) {
                $Tags->postAction(Action::Create, array('tag' => $this->faker->randomElement($tagsArr)));
            }
            // random date in the past 5 years
            $date = $this->faker->dateTimeBetween('-5 years')->format('Ymd');
            $Entity->patch(Action::Update, array('title' => $this->faker->sentence(), 'date' => $date, 'body' => $this->faker->realText(1000)));

            // lock 10% of experiments (but not the first one because it is used in tests)
            if ($this->faker->randomDigit() > 8 && $i > 1) {
                $Entity->toggleLock();
            }

            // change the visibility, but not the first ones as they are often used in tests and this could cause permissions issues
            if ($this->faker->randomDigit() > 8 && $i > 10) {
                $Entity->patch(Action::Update, array('canread' => $this->faker->randomElement($visibilityArr)));
                $Entity->patch(Action::Update, array('canwrite' => $this->faker->randomElement($visibilityArr)));
            }

            // CATEGORY
            // blank the custom_id first or we might run into an issue when changing the category because another entry has the same custom_id
            $Entity->patch(Action::Update, array('custom_id' => ''));
            $category = $this->faker->randomElement($categoryArr);
            $Entity->patch(Action::Update, array('category' => (string) $category['id']));

            // STATUS
            $status = $this->faker->randomElement($statusArr);
            $Entity->patch(Action::Update, array('status' => (string) $status['id']));

            // maybe upload a file but not on the first one
            if ($this->faker->randomDigit() > 7 && $id !== 1) {
                $Entity->Uploads->createFromString(
                    FileFromString::Json,
                    $this->faker->word() . $this->faker->word(),
                    '{ "some": "content" }',
                );
            }

            // maybe add a few steps
            if ($this->faker->randomDigit() > 8) {
                // put two words so it's long enough
                $Entity->Steps->postAction(Action::Create, array('body' => $this->faker->word() . $this->faker->word()));
                $Entity->Steps->postAction(Action::Create, array('body' => $this->faker->word() . $this->faker->word()));
            }

            // maybe make it bookable
            if ($Entity instanceof Items && $this->faker->randomDigit() > 6) {
                $Entity->patch(Action::Update, array('is_bookable' => '1'));
            }
        }
    }

    // create a user based on options provided in yaml file
    public function createUser(int $team, ?array $user = null): void
    {
        $firstname = $user['firstname'] ?? $this->faker->firstName();
        $lastname = $user['lastname'] ?? $this->faker->lastName();
        $orgid = $user['orgid'] ?? null;
        $passwordHash = (new UserParams('password', $user['password'] ?? self::DEFAULT_PASSWORD))->getStringContent();
        // use yopmail.com instead of safeEmail() so we don't hard bounce on example.tld domains when testing mass emails
        $email = $user['email'] ?? sprintf('%s-%d@yopmail.com', $this->faker->word, $this->faker->randomNumber(8));

        $Users = new Users();
        $userid = $Users->createOne(
            email: $email,
            teams: array($team),
            firstname: $firstname,
            lastname: $lastname,
            passwordHash: $passwordHash,
            usergroup: null,
            automaticValidationEnabled: true,
            alertAdmin: false,
            validUntil: null,
            orgid: $orgid
        );
        $Requester = new Users(1, 1);
        $Users = new Users($userid, $team, $Requester);

        if ($user['is_sysadmin'] ?? false) {
            $Users->update(new UserParams('is_sysadmin', 1));
        }

        if (isset($user['validated']) && !$user['validated']) {
            $Users->update(new UserParams('validated', 0));
        }

        if ($user['create_mfa_secret'] ?? false) {
            $MfaHelper = new MfaHelper($userid);
            // use a fixed secret
            $MfaHelper->secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
            $MfaHelper->saveSecret();
        }
        if ($user['create_experiments'] ?? false) {
            $this->generate(new Experiments($Users), $user['experiments_iter'] ?? $this->iter);
        }
        if ($user['create_items'] ?? false) {
            $this->generate(new Items($Users), $user['items_iter'] ?? $this->iter);
        }
        if (array_key_exists('api_key', $user ?? array())) {
            $ApiKeys = new ApiKeys($Users);
            $ApiKeys->createKnown($user['api_key'] ?? 'yep');
        }
        if (isset($user['user_preferences'])) {
            $Users->patch(Action::Update, $user['user_preferences']);
        }

        if ($user['create_templates'] ?? false) {
            $Templates = new Templates($Users);
            for ($i = 0; $i <= self::TEMPLATES_ITER; $i++) {
                $Templates->create(title: $this->faker->sentence(), body: $this->faker->realText(1000));
            }
        }
        $this->output->writeln(sprintf('├ + user: %s %s (%s) in team %d', $firstname, $lastname, $email, $team));
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
