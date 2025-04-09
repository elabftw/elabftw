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

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

class PopulateTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Populate $Populate;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Populate = new Populate(2);
    }

    public function testGenerateExperiments(): void
    {
        $this->Populate->generate(new Experiments($this->Users));
    }

    public function testGenerateItems(): void
    {
        $this->Populate->generate(new Items($this->Users));
    }
}
