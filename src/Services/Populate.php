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
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\Usergroup;
use Elabftw\Enums\UsersColumn;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\Links\Items2ExperimentsLinks;
use Elabftw\Models\Links\Items2ItemsLinks;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ResourcesCategories;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users\UltraAdmin;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Params\UserParams;
use Elabftw\Traits\RandomColorTrait;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\OutputInterface;

use function array_key_exists;
use function bin2hex;
use function dirname;
use function random_bytes;
use function sprintf;

/**
 * This is used to generate data for dev purposes
 */
final class Populate
{
    use TestsUtilsTrait;
    use RandomColorTrait;

    private const int DEFAULT_ITERATIONS = 24;

    // the password to use if none are provided
    private const string DEFAULT_PASSWORD = 'totototototo';

    // number of templates to generate
    private const int TEMPLATES_ITER = 6;

    private \Faker\Generator $faker;

    // iter: iterations: number of things to generate
    public function __construct(private OutputInterface $output, private array $yaml, private bool $fast = false, private ?int $iterations = null)
    {
        $this->faker = \Faker\Factory::create();
        $this->iterations = $this->fast ? 3 : $iterations ?? self::DEFAULT_ITERATIONS;
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

        // main loop is on "teams" key
        foreach ($this->yaml['teams'] as $team) {
            $teamid = $Teams->create($team['name']);
            $this->output->writeln(sprintf('├ + team: %s (ID: %d)', $team['name'], $teamid));
            $Teams->setId($teamid);

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
                $this->createUser($teamid, $user ?? array());
            }
            $iter = (int) ($team['random_users'] ?? 2);
            if ($this->fast) {
                $iter = 2;
            }
            for ($i = 0; $i < $iter; $i++) {
                $this->createUser($teamid, array());
            }

            // USER GROUPS
            foreach ($team['user_groups'] ?? array() as $group) {
                $teamScopedAdmin = new UltraAdmin($Users->userData['userid'], $teamid);
                $Teamgroups = new TeamGroups($teamScopedAdmin);
                $id = $Teamgroups->create($group['name']);
                $Teamgroups->setId($id);
                foreach ($group['users'] as $userid) {
                    $Teamgroups->updateMember(array(
                        'how' => Action::Add->value,
                        'userid' => $userid,
                    ));
                }
                $this->output->writeln(sprintf('├ + teamgroup: %s (id: %d in team: %d)', $group['name'], $id, $teamid));
            }

            // EXPERIMENTS CATEGORIES
            foreach ($team['experiments_categories'] ?? array() as $category) {
                $id = new ExperimentsCategories($Teams)->postAction(Action::Create, array(
                    'name' => $category['name'],
                    'color' => $category['color'] ?? $this->getRandomDarkColor(),
                ));
                $this->output->writeln(sprintf('├ + exp category: %s (id: %d in team: %d)', $category['name'], $id, $teamid));
            }

            // EXPERIMENTS STATUS
            foreach ($team['experiments_status'] ?? array() as $status) {
                $id = new ExperimentsStatus($Teams)->postAction(Action::Create, array(
                    'name' => $status['name'],
                    'color' => $status['color'] ?? $this->getRandomDarkColor(),
                ));
                $this->output->writeln(sprintf('├ + exp status: %s (id: %d in team: %d)', $status['name'], $id, $teamid));
            }

            // ITEMS STATUS
            foreach ($team['items_status'] ?? array() as $status) {
                $id = new ItemsStatus($Teams)->postAction(Action::Create, array(
                    'name' => $status['name'],
                    'color' => $status['color'] ?? $this->getRandomDarkColor(),
                ));
                $this->output->writeln(sprintf('├ + resources status: %s (id: %d in team: %d)', $status['name'], $id, $teamid));
            }

            // EXPERIMENTS TEMPLATES
            foreach ($team['templates'] ?? array() as $template) {
                $user = $this->getRandomUserInTeam($teamid);
                $Templates = new Templates($user);
                $id = $Templates->create(
                    title: $template['title'],
                    body: $template['body'] ?? '',
                    category: $Templates->getIdempotentIdFromTitle($template['category'] ?? 'Project Narwal'),
                    status: $template['status'] ?? 2,
                    metadata: $template['metadata'] ?? '{}',
                );
                $Templates->setId($id);
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

            $ResourcesCategories = new ResourcesCategories($Teams);

            // add Resources Categories
            foreach ($team['resources_categories'] ?? array() as $entry) {
                $id = $ResourcesCategories->create($entry['name'], $entry['color'] ?? $this->getRandomDarkColor());
                $this->output->writeln(sprintf('├ + resource category: %s (id: %d in team: %d)', $entry['name'], $id, $teamid));
            }

            // add Resources Templates (items types)
            foreach ($team['items_types'] ?? array() as $items_types) {
                $Admin = $this->getRandomUserInTeam($teamid, admin: 1);
                $ItemsTypes = new ItemsTypes($Admin);
                $defaultPermissions = BasePermissions::Team->toJson();
                $category = array_key_exists('category', $items_types) ? $ResourcesCategories->getIdempotentIdFromTitle($items_types['category']) : null;
                $itemTypeId = $ItemsTypes->create(
                    title: $items_types['name'],
                    body: $items_types['template'] ?? '',
                    category: $category,
                    date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                    canread: $defaultPermissions,
                    canwrite: $defaultPermissions,
                    metadata: $items_types['metadata'] ?? '{}',
                );
                $this->output->writeln(sprintf('├ + resource template: %s (id: %d in team: %d)', $items_types['name'], $itemTypeId, $teamid));
            }

            // generate random experiments before the defined ones
            if (!$this->fast) {
                $user = $this->getRandomUserInTeam($teamid);
                $this->generate(new Experiments($user));
                $this->generate(new Items($user));
            }

            // EXPERIMENTS
            foreach ($team['experiments'] ?? array() as $experiment) {
                $Experiments = new Experiments($this->getRandomUserInTeam($teamid));
                $ExperimentsCategories = new ExperimentsCategories($Teams);
                $id = $Experiments->create(
                    title: $experiment['title'],
                    body: $experiment['body'] ?? '',
                    date: new DateTimeImmutable((string) ($experiment['date'] ?? $this->faker->dateTimeBetween('-5 years')->format('Ymd'))),
                    category: $ExperimentsCategories->getIdempotentIdFromTitle($experiment['category'] ?? 'Demo'),
                    status: $experiment['status'] ?? 2,
                    metadata: $experiment['metadata'] ?? '{}',
                    rating: $experiment['rating'] ?? 0,
                );
                $Experiments->setId($id);
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

            // randomize the entries so they look like they are not added at once
            if (isset($team['items'])) {
                shuffle($team['items']);
                foreach ($team['items'] as $item) {
                    $user = $this->getRandomUserInTeam($teamid);
                    $ResourcesCategories = new ResourcesCategories($Teams);
                    $Items = new Items($user);
                    $id = $Items->create(
                        category: $ResourcesCategories->getIdempotentIdFromTitle($item['category'] ?? 'Default'),
                        title: $item['title'],
                        body: $item['body'] ?? '',
                        date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                        rating: $item['rating'] ?? 0,
                    );
                    $Items->setId($id);
                    // bookable cannot be set in create function
                    $Items->update(new EntityParams('is_bookable', $item['is_bookable'] ?? '0'));
                    // don't override the items type metadata
                    if (isset($item['metadata'])) {
                        $Items->patch(Action::Update, array('metadata' => $item['metadata']));
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
                    $this->output->writeln(sprintf('├ + resource: %s (id: %d in team: %d)', $item['title'], $id, $teamid));
                }
            }
        }

        // INVENTORY
        if (isset($this->yaml['inventory'])) {
            $StorageUnits = new StorageUnits($this->getRandomUserInTeam(1), false);
            foreach ($this->yaml['inventory'] as $entry) {
                $zones = explode('|', $entry);
                $StorageUnits->createImmutable($zones);
            }
        }

        // COMPOUNDS
        $mock = new MockHandler(array(
            new Response(200, array(), 'nothing'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, $Users, new NullFingerprinter(), false);
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
        $iterations ??= $this->iterations;
        $Teams = new Teams($Entity->Users, $Entity->Users->team, bypassWritePermission: true);
        if ($Entity instanceof Experiments) {
            $Category = new ExperimentsCategories($Teams);
            $Status = new ExperimentsStatus($Teams);
        } else {
            $Category = new ResourcesCategories($Teams);
            $Status = new ItemsStatus($Teams);
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

        $category = empty($categoryArr) ? null : $this->faker->randomElement($categoryArr)['id'];
        for ($i = 0; $i < $iterations; $i++) {
            $id = $Entity->create(
                category: $category,
                status: $this->faker->randomElement($statusArr)['id'],
                canread: $this->faker->randomElement($visibilityArr),
                canwrite: $this->faker->randomElement($visibilityArr),
                title: $this->faker->sentence(),
                date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                body: $this->faker->realText(1000),
            );
            $Entity->setId($id);
            // variable tag number
            $Tags = new Tags($Entity);
            $tagNb = $this->faker->numberBetween(0, 5);
            for ($j = 0; $j <= $tagNb; $j++) {
                $Tags->postAction(Action::Create, array('tag' => $this->faker->randomElement($tagsArr)));
            }

            // lock 10% of experiments (but not the first one because it is used in tests)
            if ($this->faker->randomDigit() > 8 && $i > 1) {
                $Entity->toggleLock();
            }

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
    private function createUser(int $team, array $user): void
    {
        $firstname = $user['firstname'] ?? $this->faker->firstName();
        $lastname = $user['lastname'] ?? $this->faker->lastName();
        $usergroup = null;
        if (array_key_exists('usergroup', $user)) {
            $usergroup = Usergroup::tryFrom($user['usergroup'] ?? Usergroup::User->value);
        }
        $orgid = $user['orgid'] ?? null;
        $canManageCompounds = BinaryValue::from((int) ($user['can_manage_compounds'] ?? 0));
        $canManageInventoryLocations = BinaryValue::from((int) ($user['can_manage_inventory_locations'] ?? 0));
        $password = $user['password'] ?? self::DEFAULT_PASSWORD;
        // special case for "random" value
        if ($password === 'random') {
            $password = bin2hex(random_bytes(24));
        }
        $passwordHash = new UserParams('password', $password)->getStringContent();
        // use yopmail.com instead of safeEmail() so we don't hard bounce on example.tld domains when testing mass emails
        $email = $user['email'] ?? sprintf('%s-%d@yopmail.com', $this->faker->word, $this->faker->randomNumber(8));

        $Users = new Users();
        $userid = $Users->createOne(
            email: $email,
            teams: array($team),
            firstname: $firstname,
            lastname: $lastname,
            passwordHash: $passwordHash,
            usergroup: $usergroup,
            automaticValidationEnabled: true,
            alertAdmin: false,
            validUntil: null,
            orgid: $orgid,
            canManageCompounds: $canManageCompounds,
            canManageInventoryLocations: $canManageInventoryLocations,
        );
        $Users = new Users($userid, $team);

        if ($user['is_sysadmin'] ?? false) {
            $Users->rawUpdate(UsersColumn::IsSysadmin, 1);
        }

        if (isset($user['validated']) && !$user['validated']) {
            $Users->rawUpdate(UsersColumn::Validated, 0);
        }

        if ($user['create_mfa_secret'] ?? false) {
            // use a fixed secret
            $secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
            $Users->update(new UserParams('mfa_secret', $secret));
        }
        if (array_key_exists('api_key', $user)) {
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
        $Db->q('DROP database ' . Env::asString('DB_NAME'));
        $Db->q('CREATE database ' . Env::asString('DB_NAME'));
        $Db->q('USE ' . Env::asString('DB_NAME'));

        // load structure
        $Sql = new Sql(new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql')));
        $Sql->execFile('structure.sql');
    }
}
