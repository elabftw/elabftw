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

use Elabftw\Elabftw\Tools;
use Elabftw\Services\UsersHelper;
use PDO;

use function date;

/**
 * Create a report of usage for users provided in construct
 */
class MakeReport extends AbstractMakeCsv
{
    public function __construct(private array $users)
    {
        parent::__construct();
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
            'created_at',
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
        foreach ($this->users as $key => $user) {
            $UsersHelper = new UsersHelper($user['userid']);
            // get the teams of user
            $teams = implode(',', $UsersHelper->getTeamsNameFromUserid());
            // get disk usage for all uploaded files
            $diskUsage = $this->getDiskUsage($user['userid']);

            // remove unused columns as they will mess up the csv
            // these columns can be null
            $unusedColumns = array(
                'mfa_secret',
                'orcid',
                'auth_service',
                'token',
                'auth_lock_time',
                'sig_pubkey',
            );
            foreach ($unusedColumns as $column) {
                unset($this->users[$key][$column]);
            }

            $this->users[$key]['team(s)'] = $teams;
            $this->users[$key]['diskusage_in_bytes'] = $diskUsage;
            $this->users[$key]['diskusage_formatted'] = Tools::formatBytes($diskUsage);
            $this->users[$key]['exp_total'] = $UsersHelper->countExperiments();
            $this->users[$key]['exp_timestamped_total'] = $UsersHelper->countTimestampedExperiments();
        }
        return $this->users;
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
