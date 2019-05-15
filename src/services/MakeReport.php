<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Tools;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Models\Teams;
use Elabftw\Models\Uploads;
use Elabftw\Traits\CsvTrait;

/**
 * Create a report of usage for all users
 */
class MakeReport extends AbstractMake
{
    use CsvTrait;

    /** @var Teams $Teams instance of Teams */
    private $Teams;

    /** @var Uploads $Uploads instance of Uploads */
    private $Uploads;

    /**
     * Constructor
     *
     * @param Teams $teams
     * @param Uploads $uploads
     */
    public function __construct(Teams $teams, Uploads $uploads)
    {
        $this->Teams = $teams;
        $this->Uploads = $uploads;
    }

    /**
     * The human friendly name
     *
     * @return string
     */
    public function getFileName(): string
    {
        return Tools::kdate() . '-report.elabftw.csv';
    }

    /**
     * Columns of the CSV
     *
     * @return array
     */
    protected function getHeader(): array
    {
        return array(
            'userid',
            'firstname',
            'lastname',
            'team',
            'email',
            'validated',
            'usergroup',
            'archived',
            'last_login',
            'teamname',
            'diskusage_in_bytes',
            'diskusage_formatted',
            'exp_total'
        );
    }

    /**
     * Get the rows for each users
     *
     * @return array
     */
    protected function getRows(): array
    {
        $allUsers = $this->Teams->Users->readFromQuery('');
        foreach ($allUsers as $key => $user) {
            // get disk usage for all uploaded files
            $diskUsage = $this->Uploads->getDiskUsage((int) $user['userid']);
            // get total number of experiments
            $Entity = new Experiments(new Users((int) $user['userid']));
            $Entity->setUseridFilter();
            $itemsArr = $Entity->read(false);
            $count = \count($itemsArr);

            $allUsers[$key]['diskusage_in_bytes'] = $diskUsage;
            $allUsers[$key]['diskusage_formatted'] = Tools::formatBytes($diskUsage);
            $allUsers[$key]['exp_total'] = $count;
        }
        return $allUsers;
    }
}
