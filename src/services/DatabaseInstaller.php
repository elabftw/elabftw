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

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Sql;
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
        $Teams->create(new ContentParams('Default team'));
    }
}
