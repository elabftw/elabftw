<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function date;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Traits\CsvTrait;
use Elabftw\Traits\UploadTrait;
use PDO;

/**
 * Create a report of usage for all users
 */
class MakeReport implements FileMakerInterface
{
    use CsvTrait;
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
            'email',
            'validated',
            'usergroup',
            'archived',
            'last_login',
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
        $allUsers = $this->Teams->Users->readFromQuery('');
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
                'phone',
                'cellphone',
                'skype',
                'website',
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
