<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;

class UnfinishedStepsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Items $Items;

    private UnfinishedSteps $UnfinishedStepsExperiments;

    private UnfinishedSteps $UnfinishedStepsItems;

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 1);
        $this->Items = new Items(new Users(1, 1), 1);
        $this->UnfinishedStepsExperiments = new UnfinishedSteps($this->Experiments);
        $this->UnfinishedStepsItems = new UnfinishedSteps($this->Items);
    }

    public function testReadExperimetsSteps(): void
    {
        $this->assertIsArray($this->UnfinishedStepsExperiments->read(new ContentParams('user')));
    }

    public function testReadItemsStepsUser(): void
    {
        $this->assertIsArray($this->UnfinishedStepsItems->read(new ContentParams('user')));
    }

    public function testReadItemsStepsTeam(): void
    {
        $this->assertIsArray($this->UnfinishedStepsItems->read(new ContentParams('team')));
    }
}
