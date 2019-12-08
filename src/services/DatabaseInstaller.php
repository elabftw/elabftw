<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Sql;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

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
