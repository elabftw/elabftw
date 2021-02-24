<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;

/**
 * This is used to generate data for dev purposes
 */
class Populate
{
    /**
     * Populate the db with fake data
     *
     * @param AbstractEntity $Entity
     * @param int $iter number of items to add
     * @return void
     */
    public function generate(AbstractEntity $Entity, int $iter = 50): void
    {
        $Faker = \Faker\Factory::create();
        if ($Entity instanceof Experiments) {
            $Category = new Status($Entity->Users);
            $tpl = 0;
        } else {
            $Category = new ItemsTypes($Entity->Users);
            $tpl = (int) $Category->readAll()[0]['category_id'];
        }
        $categories = $Category->readAll();


        printf("Generating %s \n", $Entity->type);
        for ($i = 0; $i <= $iter; $i++) {
            $id = $Entity->create(new ParamsProcessor(array('id' => $tpl)));
            $Entity->setId($id);
            // variable tag number
            $Tags = new Tags($Entity);
            for ($j = 0; $j <= $Faker->numberBetween(0, 5); $j++) {
                $Tags->create(new ParamsProcessor(array('tag' => $Faker->word)));
            }
            // random date in the past 5 years
            $Entity->update($Faker->sentence, $Faker->dateTimeBetween('-5 years')->format('Ymd'), $Faker->realText(1000));

            // lock 10% of experiments (but not the first one because it is used in tests)
            if ($Faker->randomDigit > 8 && $i > 1) {
                $Entity->toggleLock();
            }

            // change the visibility
            if ($Faker->randomDigit > 8) {
                $Entity->updatePermissions('read', $Faker->randomElement(array('organization', 'public', 'user')));
                $Entity->updatePermissions('write', $Faker->randomElement(array('organization', 'public', 'user')));
            }

            // change the category (status/item type)
            $category = $Faker->randomElement($categories);
            $Entity->updateCategory((int) $category['category_id']);

            // maybe upload a file but not on the first one
            if ($Faker->randomDigit > 7 && $id !== 1) {
                $Entity->Uploads->createFromString('json', $Faker->word, '{ "some": "content" }');
            }

            // maybe add a few steps
            if ($Faker->randomDigit > 8) {
                $Entity->Steps->create(new ParamsProcessor(array('template' => $Faker->word)));
                $Entity->Steps->create(new ParamsProcessor(array('template' => $Faker->word)));
            }
        }
        printf("Generated %d %s \n", $iter, $Entity->type);
    }
}
