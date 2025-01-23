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

use Elabftw\Enums\Action;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Items $Items;

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 3);
        $this->Items = new Items(new Users(1, 1), 3);
        $this->Experiments->ExperimentsLinks->setId(4);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/experiments/3/experiments2experiments/', $this->Experiments->ExperimentsLinks->getApiPath());
    }

    public function testCreateReadDestroy(): void
    {
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ItemsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ItemsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ItemsLinks->readAll()));

        $this->Experiments->ExperimentsLinks->setId(4);
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ExperimentsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ExperimentsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ExperimentsLinks->readAll()));
    }

    public function testImport(): void
    {
        // create a link in a db item
        $Items = new Items(new Users(1, 1), 1);
        $Items->ItemsLinks->setId(1);
        $Items->ItemsLinks->postAction(Action::Create, array());
        // now import this in our experiment like if we click the import links button
        $Links = new Items2ItemsLinks($this->Experiments, $Items->id);
        $this->assertIsInt($Links->postAction(Action::Duplicate, array()));
        $this->Experiments->ItemsLinks->setId(1);
        $this->assertIsInt($this->Experiments->ItemsLinks->postAction(Action::Duplicate, array()));
        $this->Experiments->ExperimentsLinks->setId(1);
        $this->assertIsInt($this->Experiments->ExperimentsLinks->postAction(Action::Duplicate, array()));
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->Experiments->ItemsLinks->readOne());
        $this->assertIsArray($this->Experiments->ExperimentsLinks->readOne());
    }

    public function testReadRelated(): void
    {
        $this->assertIsArray($this->Experiments->ItemsLinks->readRelated());
        $this->assertIsArray($this->Experiments->ExperimentsLinks->readRelated());
        $this->assertIsArray($this->Items->ItemsLinks->readRelated());
        $this->assertIsArray($this->Items->ExperimentsLinks->readRelated());
    }

    public function testLinksFromTemplate(): void
    {
        $Templates = new Templates(new Users(1, 1));
        // create a template
        $id = $Templates->postAction(Action::Create, array('title' => 'some template'));
        $Templates->setId($id);
        // add an item link
        $Templates->ItemsLinks->setId(1);
        $Templates->ItemsLinks->postAction(Action::Create, array());
        // now create an experiment from that template
        $expid = $this->Experiments->postAction(Action::Create, array('category_id' => $id));
        $this->Experiments->setId($expid);
        $this->assertEquals(1, count($this->Experiments->ItemsLinks->readAll()));
    }

    public function testReadExperimentsLinksFromTemplate(): void
    {
        $Templates = new Templates(new Users(1, 1), 1);
        $this->assertEmpty($Templates->ExperimentsLinks->readAll());
        $Templates->ExperimentsLinks->setId(1);
        $Templates->ExperimentsLinks->postAction(Action::Create, array());
        $this->assertEquals(1, count($Templates->ExperimentsLinks->readAll()));
    }
}
