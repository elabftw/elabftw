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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;
use PDO;
use Override;

use function date;

/**
 * Create a report of usage for users provided in construct
 */
class MakeReport extends AbstractMakeCsv
{
    protected array $users;

    public function __construct(protected Users $requester)
    {
        parent::__construct();
        $this->canReadOrExplode();
    }

    /**
     * The human friendly name
     */
    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d') . '-report.elabftw.csv';
    }

    protected function canReadOrExplode(): void
    {
        if (!$this->requester->userData['is_sysadmin']) {
            throw new IllegalActionException('Non sysadmin user tried to generate report.');
        }
    }

    protected function readUsers(): array
    {
        return $this->requester->readFromQuery('', includeArchived: true);
    }

    /**
     * Columns of the CSV
     */
    #[Override]
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
            'initials',
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
    #[Override]
    protected function getRows(): array
    {
        $users = $this->readUsers();
        foreach ($users as $key => $user) {
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
                unset($users[$key][$column]);
            }

            $users[$key]['team(s)'] = $teams;
            $users[$key]['diskusage_in_bytes'] = $diskUsage;
            $users[$key]['diskusage_formatted'] = Tools::formatBytes($diskUsage);
            $users[$key]['exp_total'] = $UsersHelper->countExperiments();
            $users[$key]['exp_timestamped_total'] = $UsersHelper->countTimestampedExperiments();
        }
        return $users;
    }

    protected function getDiskUsage(int $userid): int
    {
        $sql = 'SELECT SUM(filesize) FROM uploads WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
