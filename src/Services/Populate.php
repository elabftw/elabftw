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
use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\LocalPassword;
use Elabftw\Elabftw\Migrations;
use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\GhsSignalWord;
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Enums\Units;
use Elabftw\Enums\Usergroup;
use Elabftw\Enums\UsersColumn;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Hash\LocalPasswordHash;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\AbstractStatus;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Branding;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\ResourcesCategories;
use Elabftw\Models\Scheduler;
use Elabftw\Models\SigKeys;
use Elabftw\Models\StepGroups;
use Elabftw\Models\Steps;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\ExistingUser;
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
use function explode;
use function shuffle;
use function strtoupper;
use function array_filter;
use function array_values;
use function ctype_digit;
use function is_array;
use function is_int;
use function is_string;

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

    // each entry is array(links object, description for output)
    private array $pendingLinks = array();

    // ids of the experiments and resources defined in the config, by type of link, team and title,
    // so experiments_links and items_links can use a title instead of an id that changes on every run
    private array $idsByTitle = array();

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
        $Teams = new Teams($Users);

        // main loop is on "teams" key
        foreach ($this->yaml['teams'] as $team) {
            $teamid = $Teams->create($team['name']);
            $this->output->writeln(sprintf('├ + team: %s (ID: %d)', $team['name'], $teamid));
            // init a new object so we populate teamArr
            $Teams = new Teams($Users, $teamid);

            $columns = array(
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
            // Hide the team only after its users have been populated.
            if (isset($team['visible'])) {
                $Teams->patch(Action::Update, array(
                    'visible' => (int) $team['visible'],
                ));
            }

            // USER GROUPS
            foreach ($team['user_groups'] ?? array() as $group) {
                $teamScopedAdmin = new UltraAdmin($Users->userData['userid'], $teamid);
                $Teamgroups = new TeamGroups($teamScopedAdmin);
                $id = $Teamgroups->create($group['name']);
                $Teamgroups->setId($id);
                foreach ($group['users'] as $user) {
                    $Teamgroups->updateMember(array(
                        'how' => Action::Add->value,
                        // an email keeps the membership stable: ids shift with --fast or random users
                        'userid' => is_int($user) ? $user : ExistingUser::fromEmail($user)->userid,
                    ));
                }
                $this->output->writeln(sprintf('├ + teamgroup: %s (id: %d in team: %d)', $group['name'], $id, $teamid));
            }

            $TeamAsAdmin = new Teams(new UltraAdmin(1, $teamid), $teamid);
            // EXPERIMENTS CATEGORIES
            // a custom category set replaces the default one created along with the team
            if (!empty($team['experiments_categories'])) {
                $this->destroyCatStat(new ExperimentsCategories($TeamAsAdmin), 'experiments categories', $teamid);
            }
            foreach ($team['experiments_categories'] ?? array() as $category) {
                $id = new ExperimentsCategories($Teams)->postAction(Action::Create, array(
                    'name' => $category['name'],
                    'color' => $category['color'] ?? $this->getRandomAaaColor(),
                    'color_fg' => $category['color_fg'] ?? null,
                ));
                $this->output->writeln(sprintf('├ + exp category: %s (id: %d in team: %d)', $category['name'], $id, $teamid));
            }

            // EXPERIMENTS STATUS
            // a custom status set replaces the default one created along with the team
            if (!empty($team['experiments_status'])) {
                $this->destroyCatStat(new ExperimentsStatus($TeamAsAdmin), 'experiments status', $teamid);
            }
            foreach ($team['experiments_status'] ?? array() as $status) {
                $id = new ExperimentsStatus($Teams)->postAction(Action::Create, array(
                    'name' => $status['name'],
                    'color' => $status['color'] ?? $this->getRandomAaaColor(),
                    'color_fg' => $status['color_fg'] ?? null,
                ));
                $this->output->writeln(sprintf('├ + exp status: %s (id: %d in team: %d)', $status['name'], $id, $teamid));
            }
            // the default set (Running, Success...) uses light colors: darken them so white text stays readable
            if (empty($team['experiments_status'])) {
                $this->darkenToAaa(new ExperimentsStatus($TeamAsAdmin), $teamid);
            }

            // ITEMS STATUS
            if (!empty($team['items_status'])) {
                $this->destroyCatStat(new ItemsStatus($TeamAsAdmin), 'resources status', $teamid);
            }
            foreach ($team['items_status'] ?? array() as $status) {
                $id = new ItemsStatus($Teams)->postAction(Action::Create, array(
                    'name' => $status['name'],
                    'color' => $status['color'] ?? $this->getRandomAaaColor(),
                ));
                $this->output->writeln(sprintf('├ + resources status: %s (id: %d in team: %d)', $status['name'], $id, $teamid));
            }

            // EXPERIMENTS TEMPLATES
            foreach ($team['templates'] ?? array() as $template) {
                $Templates = new Templates($this->getOwnerInTeam($teamid, $template['owner'] ?? null));
                $id = $Templates->create(
                    title: $template['title'],
                    body: $template['body'] ?? '',
                    category: new ExperimentsCategories($Teams)->getIdempotentIdFromTitle($template['category'] ?? 'Project Narwal', $this->getRandomAaaColor()),
                    status: $this->getStatus(new ExperimentsStatus($Teams), $template['status'] ?? 2),
                    metadata: $template['metadata'] ?? '{}',
                );
                $Templates->setId($id);
                if (isset($template['hide_main_text'])) {
                    $Templates->update(new EntityParams('hide_main_text', (string) $template['hide_main_text']));
                }
                if (isset($template['locked'])) {
                    $Templates->toggleLock();
                }
                if (isset($template['tags'])) {
                    $Tags = new Tags($Templates);
                    foreach ($template['tags'] as $tag) {
                        $Tags->postAction(Action::Create, array('tag' => $tag));
                    }
                }
                $this->queueLinks($Templates, $template);
            }

            $ResourcesCategories = new ResourcesCategories($Teams);

            // ADD RESOURCES CATEGORIES
            if (!empty($team['resources_categories'])) {
                $this->destroyCatStat($ResourcesCategories, 'resources categories', $teamid);
            }
            foreach ($team['resources_categories'] ?? array() as $entry) {
                $id = $ResourcesCategories->create(
                    $entry['name'],
                    $entry['color'] ?? $this->getRandomAaaColor(),
                    $entry['color_fg'] ?? null,
                );
                $this->output->writeln(sprintf('├ + resource category: %s (id: %d in team: %d)', $entry['name'], $id, $teamid));
            }

            // ITEMS TYPES
            foreach ($team['items_types'] ?? array() as $items_types) {
                $ItemsTypes = new ItemsTypes($this->getOwnerInTeam($teamid, $items_types['owner'] ?? null));
                $category = array_key_exists('category', $items_types) ? $ResourcesCategories->getIdempotentIdFromTitle($items_types['category'], $this->getRandomAaaColor()) : null;
                $itemTypeId = $ItemsTypes->create(
                    title: $items_types['name'],
                    body: $items_types['template'] ?? '',
                    category: $category,
                    date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                    metadata: $items_types['metadata'] ?? '{}',
                    status: $this->getStatus(new ItemsStatus($Teams), $items_types['status'] ?? null),
                );
                $ItemsTypes->setId($itemTypeId);
                $this->queueLinks($ItemsTypes, $items_types);
                $this->output->writeln(sprintf('├ + resource template: %s (id: %d in team: %d)', $items_types['name'], $itemTypeId, $teamid));
            }

            // generate random experiments before the defined ones
            $user = $this->getOwnerInTeam($teamid, null);
            if ($this->yaml['generate_random_experiments'] ?? false) {
                $iterations = array_key_exists('random_experiments', $team) ? (int) $team['random_experiments'] : null;
                $this->generate(new Experiments($user), $iterations);
            }
            if ($this->yaml['generate_random_resources'] ?? false) {
                $iterations = array_key_exists('random_resources', $team) ? (int) $team['random_resources'] : null;
                $this->generate(new Items($user), $iterations);
            }

            // EXPERIMENTS
            foreach ($team['experiments'] ?? array() as $experiment) {
                $Experiments = new Experiments($this->getOwnerInTeam($teamid, $experiment['owner'] ?? null));
                $ExperimentsCategories = new ExperimentsCategories($Teams);
                $id = $Experiments->create(
                    title: $experiment['title'],
                    body: $experiment['body'] ?? '',
                    date: new DateTimeImmutable((string) ($experiment['date'] ?? $this->faker->dateTimeBetween('-5 years')->format('Ymd'))),
                    category: $ExperimentsCategories->getIdempotentIdFromTitle($experiment['category'] ?? 'Demo', $this->getRandomAaaColor()),
                    status: $this->getStatus(new ExperimentsStatus($Teams), $experiment['status'] ?? 2),
                    metadata: $experiment['metadata'] ?? '{}',
                    rating: $experiment['rating'] ?? 0,
                );
                $Experiments->setId($id);
                $this->idsByTitle['experiments_links'][$teamid][$experiment['title']] = $id;
                if (isset($experiment['step_groups'])) {
                    $StepGroups = new StepGroups($Experiments);
                    $Steps = new Steps($Experiments);
                    foreach ($experiment['step_groups'] as $group) {
                        $groupId = $StepGroups->postAction(Action::Create, array('title' => $group['title']));
                        foreach ($group['steps'] ?? array() as $step) {
                            $stepId = $Steps->postAction(Action::Create, array('body' => is_array($step) ? $step['body'] : $step, 'group_id' => $groupId));
                            if (is_array($step) && ($step['finished'] ?? false)) {
                                new Steps($Experiments, $stepId)->patch(Action::Finish, array());
                            }
                        }
                    }
                }
                if (isset($experiment['locked'])) {
                    $Experiments->toggleLock();
                }
                if (isset($experiment['tags'])) {
                    $Tags = new Tags($Experiments);
                    foreach ($experiment['tags'] as $tag) {
                        $Tags->postAction(Action::Create, array('tag' => $tag));
                    }
                }
                if (isset($experiment['comments'])) {
                    foreach ($experiment['comments'] as $comment) {
                        new Comments($Experiments)->postAction(Action::Create, array('comment' => $comment));
                    }
                }
                $this->queueLinks($Experiments, $experiment);
                $this->output->writeln(sprintf('├ + experiment: %s (id: %d in team: %d)', $experiment['title'], $id, $teamid));
            }

            // randomize the entries so they look like they are not added at once

            // ITEMS
            $itemIds = array();
            if (isset($team['items'])) {
                shuffle($team['items']);
                foreach ($team['items'] as $item) {
                    $Items = new Items($this->getOwnerInTeam($teamid, $item['owner'] ?? null));
                    $ResourcesCategories = new ResourcesCategories($Teams);
                    $id = $Items->create(
                        title: $item['title'],
                        body: $item['body'] ?? '',
                        date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                        category: $ResourcesCategories->getIdempotentIdFromTitle($item['category'] ?? 'Default', $this->getRandomAaaColor()),
                        status: $this->getStatus(new ItemsStatus($Teams), $item['status'] ?? null),
                        rating: $item['rating'] ?? 0,
                    );
                    $Items->setId($id);
                    // Keep explicit resource ids by title because configured events refer to those titles later
                    $itemIds[$item['title']] = $id;
                    $this->idsByTitle['items_links'][$teamid][$item['title']] = $id;
                    // bookable cannot be set in create function
                    $Items->update(new EntityParams('is_bookable', $item['is_bookable'] ?? '0'));
                    // don't override the items type metadata
                    if (isset($item['metadata'])) {
                        $Items->patch(Action::Update, array('metadata' => $item['metadata']));
                    }
                    $this->storeItem($Items, $item['storage'] ?? array());
                    $this->queueLinks($Items, $item);
                    $this->output->writeln(sprintf('├ + resource: %s (id: %d in team: %d)', $item['title'], $id, $teamid));
                }
            }

            // Create configured bookings through Scheduler so populate data follows normal booking validation
            foreach ($team['events'] ?? array() as $event) {
                $itemId = $itemIds[$event['item']] ?? null;
                if ($itemId === null) {
                    throw new ResourceNotFoundException(sprintf('Could not find resource "%s" for populated event.', $event['item']));
                }
                $user = $this->getRandomUserInTeam($teamid);
                $Scheduler = new Scheduler(new Items($user, $itemId));
                $id = $Scheduler->postAction(Action::Create, array(
                    'title' => $event['title'] ?? '',
                    'start' => (new DateTimeImmutable($event['start']))->format('c'),
                    'end' => (new DateTimeImmutable($event['end']))->format('c'),
                    'recurrence' => $event['recurrence'] ?? null,
                ));
                $this->output->writeln(sprintf('├ + event: %s (id: %d for resource: %s)', $event['title'] ?? '', $id, $event['item']));
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
        $Compounds = new Compounds($httpGetter, $Users, new OpenBabelFingerprinter(), false);
        foreach ($this->yaml['compounds'] ?? array() as $compound) {
            $id = $Compounds->create(
                inchi: $compound['inchi'],
                inchiKey: $compound['inchi_key'],
                name: $compound['name'],
                casNumber: $compound['cas_number'],
                iupacName: $compound['iupac_name'],
                molecularFormula: $compound['molecular_formula'],
                molecularWeight: (float) $compound['molecular_weight'],
                pubchemCid: (int) $compound['pubchem_cid'],
                smiles: $compound['smiles'],
                ghsSignalWord: GhsSignalWord::tryFrom($compound['ghs_signal_word'] ?? ''),
                ghsStatements: Compound::extractGhsCodes($compound['ghs_statements'] ?? ''),
                // these are PubChem compounds: like an import from PubChem, every team can see them,
                // otherwise only the first team can read them and links from other teams are refused
                canreadBase: Compounds::PUBCHEM_CANREAD_BASE,
                canwriteBase: Compounds::PUBCHEM_CANWRITE_BASE,
            );
            $this->output->writeln(sprintf('├ + compound: %s (ID: %d)', $compound['name'], $id));
        }
        $this->createLinks();
    }

    /**
     * Links are created at the very end, once every possible target (including compounds) exists
     */
    private function queueLinks(AbstractEntity $Entity, array $config): void
    {
        $factories = array(
            'experiments_links' => LinksFactory::getExperimentsLinks(...),
            'items_links' => LinksFactory::getItemsLinks(...),
            'compounds_links' => LinksFactory::getCompoundsLinks(...),
        );
        foreach ($factories as $type => $factory) {
            foreach ($config[$type] ?? array() as $target) {
                // the target is resolved later: a resource given by its title may not be created yet
                $this->pendingLinks[] = array($factory, $Entity, $type, $target, $Entity->Users->team);
            }
        }
    }

    private function createLinks(): void
    {
        foreach ($this->pendingLinks as [$factory, $Entity, $type, $target, $teamid]) {
            $targetId = $this->getLinkTargetId($type, $target, $teamid);
            $description = sprintf('%s %d → %s %d', $Entity->entityType->value, $Entity->id ?? 0, $type, $targetId);
            if ($targetId != $target) {
                $description .= sprintf(' (%s)', $target);
            }
            try {
                $factory($Entity, $targetId)->postAction(Action::Create, array());
                $this->output->writeln(sprintf('├ + links: %s', $description));
            } catch (ResourceNotFoundException | ForbiddenException) {
                // let it fail, we don't care
            }
        }
    }

    private function getLinkTargetId(string $type, int|string $target, int $teamid): int
    {
        if (is_int($target) || ctype_digit($target)) {
            return (int) $target;
        }
        $known = $this->idsByTitle[$type][$teamid] ?? array();
        return $known[$target] ?? throw new ResourceNotFoundException(sprintf('Could not find resource "%s" in team %d for items_links.', $target, $teamid));
    }

    /**
     * Populate the db with fake experiments or resources
     */
    private function generate(Experiments | Items $Entity, ?int $iterations = null): void
    {
        // do nothing in fast mode
        if ($this->fast) {
            return;
        }
        $iterations ??= $this->iterations;
        $Teams = new Teams($Entity->Users, $Entity->Users->team);
        if ($Entity instanceof Experiments) {
            $Category = new ExperimentsCategories($Teams);
            $Status = new ExperimentsStatus($Teams);
        } else {
            $Category = new ResourcesCategories($Teams);
            $Status = new ItemsStatus($Teams);
        }
        $categoryArr = array_values(array_filter($Category->readAll(), fn(array $category): bool => (int) $category['team'] === $Teams->id));
        $statusArr = $Status->readAll();

        // we will randomly pick from these for canread and canwrite
        $visibilityArr = BasePermissions::cases();

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

        for ($i = 0; $i < $iterations; $i++) {
            $category = empty($categoryArr) ? null : $this->faker->randomElement($categoryArr)['id'];
            $id = $Entity->create(
                title: $this->faker->sentence(),
                body: $this->faker->realText(1000),
                date: new DateTimeImmutable($this->faker->dateTimeBetween('-5 years')->format('Ymd')),
                canreadBase: $this->faker->randomElement($visibilityArr),
                canwriteBase: $this->faker->randomElement($visibilityArr),
                category: $category,
                status: $this->faker->randomElement($statusArr)['id'],
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
                $Steps = new Steps($Entity);
                // put two words so it's long enough
                $Steps->postAction(Action::Create, array('body' => $this->faker->word() . $this->faker->word()));
                $Steps->postAction(Action::Create, array('body' => $this->faker->word() . $this->faker->word()));
            }

            // maybe make it bookable
            if ($Entity instanceof Items && $this->faker->randomDigit() > 6) {
                $Entity->patch(Action::Update, array('is_bookable' => '1'));
            }
        }
    }

    /**
     * Remove all the categories or all the status of a team, such as the default status set (Running, Success...)
     * categories and status share the same parent class (AbstractStatus), so one function covers both
     * readAllIgnoreState() is used because readAll() also lists the non private rows of other teams
     */
    private function destroyCatStat(AbstractStatus $Status, string $label, int $teamid): void
    {
        foreach ($Status->readAllIgnoreState() as $status) {
            $Status->setId((int) $status['id']);
            $Status->destroy();
            $this->output->writeln(sprintf('├ - default %s: %s (id: %d in team: %d)', $label, $status['title'], $status['id'], $teamid));
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
        $LocalPassword = new LocalPassword(
            new PasswordValidator(12, PasswordComplexity::None, $password),
            new LocalPasswordHash($password),
        );
        // use yopmail.com instead of safeEmail() so we don't hard bounce on example.tld domains when testing mass emails
        $email = $user['email'] ?? sprintf('%s-%d@yopmail.com', $this->faker->word, $this->faker->randomNumber(8));

        $Users = new Users();
        $userid = $Users->createOne(
            email: $email,
            teams: array($team),
            localPassword: $LocalPassword,
            firstname: $firstname,
            lastname: $lastname,
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
        // with a known passphrase, the user can sign entities without generating a key first
        if (array_key_exists('sig_key_passphrase', $user)) {
            new SigKeys($Users)->postAction(Action::Create, array('passphrase' => (string) $user['sig_key_passphrase']));
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

    private function getOwnerInTeam(int $teamid, ?string $email): Users
    {
        if ($email === null) {
            return $this->getRandomUserInTeam($teamid);
        }
        return new AuthenticatedUser(ExistingUser::fromEmail($email)->userid, $teamid);
    }

    private function darkenToAaa(AbstractStatus $Status, int $teamid): void
    {
        foreach ($Status->readAllIgnoreState() as $status) {
            $color = self::toAaaColor($status['color']);
            if ($color === '#' . strtoupper($status['color'])) {
                continue;
            }
            $Status->setId((int) $status['id']);
            $Status->patch(Action::Update, array('color' => $color));
            $this->output->writeln(sprintf('├ ~ status color: %s #%s → %s (in team: %d)', $status['title'], $status['color'], $color, $teamid));
        }
    }

    /**
     * A status is given by its id (used as is) or by its name (always the same status of the team)
     */
    private function getStatus(AbstractStatus $Status, int|string|null $status): ?int
    {
        if (!is_string($status)) {
            return $status;
        }
        // only the active statuses of this team: readAll() also lists the non private ones of other teams
        foreach ($Status->readAll() as $row) {
            if ((int) $row['is_current_team'] === 1 && $row['title'] === $status) {
                return (int) $row['id'];
            }
        }
        return null;
    }

    private function storeItem(Items $Items, array $storage): void
    {
        $StorageUnits = new StorageUnits($Items->Users, requireEditRights: false);
        foreach ($storage as $entry) {
            $StorageUnitId = $StorageUnits->createImmutable(explode('|', $entry['location'] ?? ''));
            if ($StorageUnitId === 0) {
                $this->output->writeln(sprintf('├ ! storage: skipped entry without location for resource %d', $Items->id ?? 0));
                continue;
            }
            $quantity = (float) ($entry['quantity'] ?? 1);
            $unit = $entry['unit'] ?? Units::Unit->value;
            new Containers2ItemsLinks($Items, $StorageUnitId)->createWithQuantity($quantity, $unit, enforceCapacity: false);
            $this->output->writeln(sprintf('├ + storage: %s (%s %s)', $entry['location'], $quantity, $unit));
        }
    }

    private function dropAndInitDb(): void
    {
        $Db = Db::getConnection();
        $Db->q('DROP database ' . Env::asString('DB_NAME'));
        $Db->q('CREATE database ' . Env::asString('DB_NAME'));
        $Db->q('USE ' . Env::asString('DB_NAME'));

        // load structure
        $sqlFs = new Fs(new LocalFilesystemAdapter(dirname(__DIR__) . '/sql'));
        $Sql = new Sql($sqlFs, $this->output);
        $Sql->execFile('structure.sql');
        (new Migrations($sqlFs, useBuildInfo: !Env::asBool('DEV_MODE')))->markAllAvailableAsApplied();
        new Branding(true)->populate();
    }
}
