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


        for ($i = 0; $i <= $iter; $i++) {
            $id = $Entity->create(1);
            $Entity->setId($id);
            $Tags = new Tags($Entity);
            $Tags->create('auto-generated');
            $Tags->create($Faker->word);
            $Tags->create($Faker->word);
            $Entity->update($Faker->sentence, $Faker->dateTimeThisCentury->format('Ymd'), $Faker->text);
        }
        printf("Generated %d %s \n", $iter, $Entity->type);
    }
}
