<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Services;

use Elabftw\Interfaces\CreateInterface;
use Elabftw\Models\Users;
use Elabftw\Models\Experiments;
use Elabftw\Models\Database;
use Elabftw\Models\Tags;

/**
 * This is used to generate data for dev purposes
 * Call it with: "docker exec -it elabftw php /elabftw/src/tools/populate-db.php
 * Change ITERATIONS as needed
 */
class Populate
{
    private const ITERATIONS = 100;

    public function generate(CreateInterface $Entity): void
    {
        for ($i = 0; $i <= self::ITERATIONS; $i++) {
            $id = $Entity->create(1);
            $Entity->setId($id);
            $Tags = new Tags($Entity);
            $Tags->create('generated tag ' . $i);
            $Tags->create('generated tag');
        }
        printf("Generated %d %s \n", self::ITERATIONS, $Entity->type);
    }
}

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once \dirname(__DIR__, 2) . '/config.php';
$Users = new Users(1);
$Experiments = new Experiments($Users);
$Database = new Database($Users);
$Populate = new Populate();
$Populate->generate($Experiments);
$Populate->generate($Database);
