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

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\FileFromString;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Tags;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Elabftw\Params\UserParams;

/**
 * This is used to generate data for dev purposes
 */
final class Populate
{
    // the password to use if none are provided
    private const string DEFAULT_PASSWORD = 'totototototo';

    // number of templates to generate
    private const int TEMPLATES_ITER = 5;

    private \Faker\Generator $faker;

    // iter: iterations: number of things to generate
    public function __construct(private int $iter = 50)
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * Populate the db with fake experiments or items
     */
    public function generate(Experiments | Items $Entity): void
    {
        $Teams = new Teams($Entity->Users, $Entity->Users->team);
        $Teams->bypassWritePermission = true;
        if ($Entity instanceof Experiments) {
            $Category = new ExperimentsCategories($Teams);
            $Status = new ExperimentsStatus($Teams);
            $tpl = 0;
        } else {
            $Category = new ItemsTypes($Entity->Users);
            $Category->bypassWritePermission = true;
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

        for ($i = 0; $i <= $this->iter; $i++) {
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
                $Entity->Uploads->postAction(Action::CreateFromString, array(
                    'file_type' => FileFromString::Json->value,
                    'real_name' => $this->faker->word() . $this->faker->word(),
                    'content' => '{ "some": "content" }',
                ));
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
    public function createUser(Teams $Teams, array $user): void
    {
        $firstname = $user['firstname'] ?? $this->faker->firstName();
        $lastname = $user['lastname'] ?? $this->faker->lastName();
        $orgid = $user['orgid'] ?? null;
        $passwordHash = (new UserParams('password', $user['password'] ?? self::DEFAULT_PASSWORD))->getStringContent();
        // use yopmail.com instead of safeEmail() so we don't hard bounce on example.tld domains when testing mass emails
        $email = $user['email'] ?? sprintf('elabuser-%d@yopmail.com', $this->faker->randomNumber(6));

        $userid = $Teams->Users->createOne(
            email: $email,
            teams: array($user['team']),
            firstname: $firstname,
            lastname: $lastname,
            passwordHash: $passwordHash,
            usergroup: null,
            automaticValidationEnabled: true,
            alertAdmin: false,
            validUntil: null,
            orgid: $orgid
        );
        $team = $Teams->getTeamsFromIdOrNameOrOrgidArray(array($user['team']), true);
        $Requester = new Users(1, 1);
        $Users = new Users($userid, $team[0]['id'], $Requester);

        if ($user['is_sysadmin'] ?? false) {
            $Users->patch(Action::Update, array('is_sysadmin' => 1));
        }

        if (isset($user['validated']) && !$user['validated']) {
            $Users->patch(Action::Update, array('validated' => 0));
        }

        if ($user['create_mfa_secret'] ?? false) {
            $MfaHelper = new MfaHelper($userid);
            // use a fixed secret
            $MfaHelper->secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
            $MfaHelper->saveSecret();
        }
        if ($user['create_experiments'] ?? false) {
            $this->generate(new Experiments($Users));
        }
        if ($user['create_items'] ?? false) {
            $this->generate(new Items($Users));
        }
        if ($user['api_key'] ?? false) {
            $ApiKeys = new ApiKeys($Users);
            $ApiKeys->createKnown($user['api_key']);
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
    }
}
