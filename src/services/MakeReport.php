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

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Traits\CsvTrait;
use Elabftw\Traits\UploadTrait;

/**
 * Create a report of usage for all users
 */
class MakeReport
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
        return Filter::kdate() . '-report.elabftw.csv';
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

            // remove mfa column
            unset($allUsers[$key]['mfa_secret']);

            $allUsers[$key]['team(s)'] = $teams;
            $allUsers[$key]['diskusage_in_bytes'] = $diskUsage;
            $allUsers[$key]['diskusage_formatted'] = Tools::formatBytes($diskUsage);
            $allUsers[$key]['exp_total'] = $UsersHelper->countExperiments();
            $allUsers[$key]['exp_timestamped_total'] = $UsersHelper->countTimestampedExperiments();
        }
        return $allUsers;
    }
}
