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

use DateInterval;
use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Models\Scheduler;
use Elabftw\Traits\TestsUtilsTrait;

class MakeSchedulerReportTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeSchedulerReport $Make;

    protected function setUp(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Item = $this->getFreshItemWithGivenUser($user);
        $Scheduler = new Scheduler($Item);
        $d = new DateTimeImmutable('+3 hour');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $Scheduler->postAction(Action::Create, array(
            'title' => 'for test',
            'start' => $start,
            'end' => $end,
        ));
        $this->Make = new MakeSchedulerReport($Scheduler);
    }

    public function testGenerate(): void
    {
        $this->assertIsString($this->Make->getFileContent());
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-report.elabftw.csv/', $this->Make->getFileName());
    }
}
