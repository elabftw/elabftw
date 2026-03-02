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
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Scheduler;
use Override;

use function date;

/**
 * Create a report of scheduler bookings
 */
final class MakeSchedulerReport extends AbstractMakeCsv
{
    protected Db $Db;

    protected array $rows;

    public function __construct(Scheduler $scheduler, ?QueryParamsInterface $queryParams = null)
    {
        $this->Db = Db::getConnection();
        $this->rows = $scheduler->readAll($queryParams);
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
     * Get the rows for each users
     */
    #[Override]
    protected function getRows(): array
    {
        return $this->rows;
    }
}
