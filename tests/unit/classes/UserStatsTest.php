<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Users;

class UserStatsTest extends \PHPUnit\Framework\TestCase
{
    private UserStats $UserStats;

    protected function setUp(): void
    {
        $this->UserStats = new UserStats(new Users(1, 1), 42);
    }

    public function testGetPieData(): void
    {
        $this->assertIsArray($this->UserStats->getPieData());
    }

    public function testNoExperiments(): void
    {
        $UserStats = new UserStats(new Users(1, 1), 0);
        $this->assertEmpty($UserStats->getPieData());
    }

    public function testGetFormattedPieData(): void
    {
        $this->assertIsString($this->UserStats->getFormattedPieData());
    }
}
