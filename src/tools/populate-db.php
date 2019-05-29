<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once \dirname(__DIR__, 2) . '/config.php';

try {
    $Users = new Users(1);
    $Experiments = new Experiments($Users);
    $Database = new Database($Users);
    $Populate = new Populate();
    $Populate->generate($Experiments);
    $Populate->generate($Database);
} catch (DatabaseErrorException $e) {
    echo $e->getMessage();
}
