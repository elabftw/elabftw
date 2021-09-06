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
use Elabftw\Elabftw\UnfinishedStepsParams;

class UnfinishedStepsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Items $Items;

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 1);
        $this->Items = new Items(new Users(1, 1), 1);
    }

    public function testReadExperimetsStepsUser(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Experiments, new UnfinishedStepsParams(array('scope' => 'user'))))->read(new ContentParams()));
    }

    public function testReadExperimetsStepsTeam(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Experiments, new UnfinishedStepsParams(array('scope' => 'user'))))->read(new ContentParams()));
    }

    public function testReadItemsStepsUser(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Items, new UnfinishedStepsParams(array('scope' => 'user'))))->read(new ContentParams()));
    }

    public function testReadItemsStepsTeam(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Items, new UnfinishedStepsParams(array('scope' => 'user'))))->read(new ContentParams()));
    }
}
