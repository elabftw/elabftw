<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

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
    public function generate(AbstractEntity $Entity, int $iter = 100): void
    {
        $Faker = \Faker\Factory::create();
        if ($Entity instanceof Experiments) {
            $Category = new Status($Entity->Users);
        } else {
            $Category = new ItemsTypes($Entity->Users);
        }
        $categories = $Category->readAll();


        printf("Generating %s \n", $Entity->type);
        for ($i = 0; $i <= $iter; $i++) {
            $id = $Entity->create(1);
            $Entity->setId($id);
            // variable tag number
            $Tags = new Tags($Entity);
            for ($j = 0; $j <= $Faker->numberBetween(0, 5); $j++) {
                $Tags->create($Faker->word);
            }
            // random date in the past 5 years
            $Entity->update($Faker->sentence, $Faker->dateTimeBetween('-5 years')->format('Ymd'), $Faker->realText(1000));

            // lock 10% of experiments (but not the first one because it is used in tests)
            if ($Faker->optional(0.9)->randomDigit === null && $i > 1) {
                $Entity->toggleLock();
            }

            // change the visibility
            if ($Faker->optional(0.9)->randomDigit === null) {
                $Entity->updatePermissions('read', $Faker->randomElement(array('organization', 'public', 'user')));
                $Entity->updatePermissions('write', $Faker->randomElement(array('organization', 'public', 'user')));
            }

            // change the category (status/item type)
            $category = $Faker->randomElement($categories);
            $Entity->updateCategory((int) $category['category_id']);
        }
        printf("Generated %d %s \n", $iter, $Entity->type);
    }
}
