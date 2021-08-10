<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\StepParams;
use Elabftw\Elabftw\TagParams;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;

/**
 * This is used to generate data for dev purposes
 */
class Populate
{
    /** @var string DEFAULT_PASSWORD the password to use if none are provided */
    private const DEFAULT_PASSWORD = 'totototo';

    private \Faker\Generator $faker;

    // number of things to generate
    private int $iter = 50;

    public function __construct(?int $iter = null)
    {
        $this->faker = \Faker\Factory::create();
        if ($iter !== null) {
            $this->iter = $iter;
        }
    }

    /**
     * Populate the db with fake experiments or items
     */
    public function generate(Experiments|Items $Entity): void
    {
        if ($Entity instanceof Experiments) {
            $Category = new Status($Entity->Users->team);
            $tpl = 0;
        } else {
            $Category = new ItemsTypes($Entity->Users);
            $tpl = (int) $Category->readAll()[0]['category_id'];
        }
        $categories = $Category->readAll();


        printf("Generating %s \n", $Entity->type);
        for ($i = 0; $i <= $this->iter; $i++) {
            $id = $Entity->create(new EntityParams((string) $tpl));
            $Entity->setId($id);
            // variable tag number
            $Tags = new Tags($Entity);
            $tagNb = $this->faker->numberBetween(0, 5);
            for ($j = 0; $j <= $tagNb; $j++) {
                $Tags->create(new TagParams($this->faker->word() . $this->faker->word()));
            }
            $params = new EntityParams($this->faker->sentence(), 'title');
            $Entity->update($params);
            // random date in the past 5 years
            $params = new EntityParams($this->faker->dateTimeBetween('-5 years')->format('Ymd'), 'date');
            $Entity->update($params);
            $params = new EntityParams($this->faker->realText(1000), 'body');
            $Entity->update($params);

            // lock 10% of experiments (but not the first one because it is used in tests)
            if ($this->faker->randomDigit() > 8 && $i > 1) {
                $Entity->toggleLock();
            }

            // change the visibility
            if ($this->faker->randomDigit() > 8) {
                $Entity->updatePermissions('read', $this->faker->randomElement(array('organization', 'public', 'user')));
                $Entity->updatePermissions('write', $this->faker->randomElement(array('organization', 'public', 'user')));
            }

            // change the category (status/item type)
            $category = $this->faker->randomElement($categories);
            $Entity->updateCategory((int) $category['category_id']);

            // maybe upload a file but not on the first one
            if ($this->faker->randomDigit() > 7 && $id !== 1) {
                $Entity->Uploads->createFromString('json', $this->faker->word() . $this->faker->word(), '{ "some": "content" }');
            }

            // maybe add a few steps
            if ($this->faker->randomDigit() > 8) {
                // put two words so it's long enough
                $Entity->Steps->create(new StepParams($this->faker->word() . $this->faker->word()));
                $Entity->Steps->create(new StepParams($this->faker->word() . $this->faker->word()));
            }
        }
        printf("Generated %d %s \n", $this->iter, $Entity->type);
    }

    // create a user based on options provided in yaml file
    public function createUser(Teams $Teams, array $user): void
    {
        $firstname = $user['firstname'] ?? $this->faker->firstName();
        $lastname = $user['lastname'] ?? $this->faker->lastName();
        $password = $user['password'] ?? self::DEFAULT_PASSWORD;
        $email = $user['email'] ?? $this->faker->safeEmail();

        $userid = $Teams->Users->create($email, array($user['team']), $firstname, $lastname, $password, null, true, false);
        $team = $Teams->getTeamsFromIdOrNameOrOrgidArray(array($user['team']));
        $Users = new Users($userid, (int) $team[0]['id']);

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

        if ($user['create_templates'] ?? false) {
            $Templates = new Templates($Users);
            for ($i = 0; $i < $this->iter; $i++) {
                $Templates->create(new EntityParams(
                    $this->faker->sentence(),
                    '',
                    array('body' => $this->faker->realText(1000)),
                ));
            }
        }
    }
}
