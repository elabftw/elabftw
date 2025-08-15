<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Models\Users\Users;

class UnfinishedStepsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
    }

    public function testReadStepsUser(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Users))->readAll());
    }

    public function testReadStepsTeam(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Users, true))->readAll());
    }
}
