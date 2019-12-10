<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Sql;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;

/**
 * Make sure the database is consistent with no leftover things
 */
class DatabaseInstaller
{
    /**
     * Load the structure into the database and create the first team
     *
     * @return void
     */
    public function install(): void
    {
        require_once \dirname(__DIR__, 2) . '/config.php';
        $Sql = new Sql();
        $Sql->execFile('structure.sql');

        $Teams = new Teams(new Users());
        $Teams->create('Default team');
    }
}
