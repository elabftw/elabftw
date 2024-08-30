<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Enums\Action;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Users;

class MakeSchedulerReportTest extends \PHPUnit\Framework\TestCase
{
    private MakeSchedulerReport $Make;

    protected function setUp(): void
    {
        $Scheduler = new Scheduler(new Items(new Users(1, 1), 1));
        $Scheduler->postAction(Action::Create, array(
            'title' => 'for test',
            'start' => '2023-01-01T13:37:00+02:00',
            'end' => '2023-01-01T15:37:00+02:00',
        ));
        $this->Make = new MakeSchedulerReport($Scheduler);
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-report.elabftw.csv/', $this->Make->getFileName());
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }
}
