<?php declare(strict_types=1);
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

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 3);
        $this->Experiments->ExperimentsLinks->setId(3);
    }

    public function testCreateReadDestroy(): void
    {
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ItemsLinks->destroy();

        $this->Experiments->ExperimentsLinks->setId(3);
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $this->Experiments->ExperimentsLinks->destroy();
    }

    public function testImport(): void
    {
        // create a link in a db item
        $Items = new Items(new Users(1, 1), 1);
        $Items->ItemsLinks->setId(1);
        $Items->ItemsLinks->postAction(Action::Create, array('targetEntityType' => 'items'));
        // now import this in our experiment like if we click the import links button
        $Links = new ItemsLinks($this->Experiments, $Items->id);
        $this->assertIsInt($Links->postAction(Action::Duplicate, array()));
    }

    public function testPatch(): void
    {
        $this->assertIsArray($this->Experiments->ItemsLinks->patch(Action::Duplicate, array()));
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
    }
}
