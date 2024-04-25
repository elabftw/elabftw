<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Models\Teams;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\UploadTrait;
use PDO;

use function date;

/**
 * Create a report of usage for all users
 */
class MakeReport extends AbstractMakeCsv
{
    use UploadTrait;

    protected Db $Db;

    public function __construct(private Teams $Teams)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * The human friendly name
     */
    public function getFileName(): string
    {
        return date('Y-m-d') . '-report.elabftw.csv';
    }

    /**
     * Columns of the CSV
     */
    protected function getHeader(): array
    {
        return array(
            'userid',
            'firstname',
            'lastname',
            'orgid',
            'email',
            'has_mfa_enabled',
            'validated',
            'archived',
            'last_login',
            'valid_until',
            'is_sysadmin',
            'full_name',
            'team(s)',
            'diskusage_in_bytes',
            'diskusage_formatted',
            'exp_total',
            'exp_timestamped_total',
        );
    }

    /**
     * Get the rows for each users
     */
    protected function getRows(): array
    {
        $allUsers = $this->Teams->Users->readFromQuery('', includeArchived: true);
        foreach ($allUsers as $key => $user) {
            $UsersHelper = new UsersHelper((int) $user['userid']);
            // get the teams of user
            $teams = implode(',', $UsersHelper->getTeamsNameFromUserid());
            // get disk usage for all uploaded files
            $diskUsage = $this->getDiskUsage((int) $user['userid']);

            // remove unused columns as they will mess up the csv
            // these columns can be null
            $unusedColumns = array(
                'mfa_secret',
                'orcid',
                'auth_service',
                'token',
                'auth_lock_time',
            );
            foreach ($unusedColumns as $column) {
                unset($allUsers[$key][$column]);
            }

            $allUsers[$key]['team(s)'] = $teams;
            $allUsers[$key]['diskusage_in_bytes'] = $diskUsage;
            $allUsers[$key]['diskusage_formatted'] = Tools::formatBytes($diskUsage);
            $allUsers[$key]['exp_total'] = $UsersHelper->countExperiments();
            $allUsers[$key]['exp_timestamped_total'] = $UsersHelper->countTimestampedExperiments();
        }
        return $allUsers;
    }

    private function getDiskUsage(int $userid): int
    {
        $sql = 'SELECT SUM(filesize) FROM uploads WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
