<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;

/**
 * Help with translation of permission json into meaningful data
 */
final class PermissionsHelper
{
    protected Db $Db;

    protected Users $Users;

    protected TeamGroups $TeamGroups;

    public function __construct()
    {
        $this->Db = Db::getConnection();
        $this->Users = new Users();
        $this->TeamGroups = new TeamGroups($this->Users);
    }

    public function translate(string $json): array
    {
        $permArr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $Teams = new Teams($this->Users);
        $result = array();

        $base = BasePermissions::tryFrom($permArr['base']) ?? throw new ImproperActionException('Invalid base parameter for permissions');
        $result['base'] = BasePermissions::toHuman($base);
        $result['teams'] = $Teams->readNamesFromIds($permArr['teams']);
        $result['teamgroups'] = $this->TeamGroups->readNamesFromIds($permArr['teamgroups']);
        $result['users'] = $this->Users->readNamesFromIds($permArr['users']);

        return $result;
    }
}
