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
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Users;
use Elabftw\Traits\CsvTrait;
use Elabftw\Traits\UploadTrait;
use function implode;

/**
 * Create a report of scheduler bookings
 */
class MakeSchedulerReport implements FileMakerInterface
{
    use CsvTrait;
    use UploadTrait;

    protected Db $Db;

    public function __construct(private Scheduler $scheduler, private string $from, private string $to)
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
            'title',
            'id',
            'start',
            'end',
            'userid',
            'item_title',
            'color',
            'fullname',
            'team(s)',
        );
    }

    /**
     * Get the rows for each users
     */
    protected function getRows(): array
    {
        // read all booking entries from that time period
        $entries = $this->scheduler->readAllFromTeam($this->from, $this->to);
        foreach ($entries as $key => $entry) {
            // append the team(s) of user
            $UsersHelper = new UsersHelper((int) $entry['userid']);
            $teams = implode(',', $UsersHelper->getTeamsNameFromUserid());
            $entries[$key]['team(s)'] = $teams;
        }
        return $entries;
    }
}
