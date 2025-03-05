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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Scheduler;
use Elabftw\Services\UsersHelper;
use Override;

use function date;
use function implode;

/**
 * Create a report of scheduler bookings
 */
final class MakeSchedulerReport extends AbstractMakeCsv
{
    protected Db $Db;

    protected array $rows;

    public function __construct(Scheduler $scheduler)
    {
        $this->Db = Db::getConnection();
        $this->rows = $scheduler->readAll();
        if (empty($this->rows)) {
            throw(new ImproperActionException('There are no events to report'));
        }
    }

    /**
     * The human friendly name
     */
    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d') . '-report.elabftw.csv';
    }

    /**
     * Columns of the CSV
     */
    #[Override]
    protected function getHeader(): array
    {
        $header = array_keys($this->rows[0]);
        $header[] = 'team(s)';
        return $header;
    }

    /**
     * Get the rows for each users
     */
    #[Override]
    protected function getRows(): array
    {
        foreach ($this->rows as $key => $entry) {
            // append the team(s) of user
            $UsersHelper = new UsersHelper($entry['userid']);
            $teams = implode(',', $UsersHelper->getTeamsNameFromUserid());
            $this->rows[$key]['team(s)'] = $teams;
        }
        return $this->rows;
    }
}
