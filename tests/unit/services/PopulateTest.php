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

use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class PopulateTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Populate = new Populate();
    }

    public function testGenerateExperiments()
    {
        $this->Populate->generate(new Experiments($this->Users), 2);
    }

    public function testGenerateItems()
    {
        $this->Populate->generate(new Database($this->Users), 2);
    }
}
