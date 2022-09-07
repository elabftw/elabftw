<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Sql;
use Elabftw\Enums\Action;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;

/**
 * Called during initial install
 */
class DatabaseInstaller
{
    public function __construct(private Sql $Sql)
    {
    }

    /**
     * Load the structure into the database and create the first team
     */
    public function install(): void
    {
        $this->Sql->execFile('structure.sql');

        $Teams = new Teams(new Users());
        $Teams->postAction(Action::Create, array('name' => 'Default team'));
    }
}
