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
        $this->Experiments->Links->setId(3);
    }

    public function testCreateReadDestroy(): void
    {
        $this->Experiments->Links->postAction(Action::Create, array('targetEntityType' => 'items'));
        $this->assertIsArray($this->Experiments->Links->readAll());
        $this->assertIsArray($this->Experiments->Links->readOne());
        $this->Experiments->Links->setId(1);
        //$this->Experiments->Links->destroy(new ContentParams(extra: array('targetEntity' => 'items')));
        $this->Experiments->Links->setId(2);
        //$this->Experiments->Links->destroy(new ContentParams(extra: array('targetEntity' => 'experiments')));
    }

    public function testImport(): void
    {
        // create a link in a db item
        $Items = new Items(new Users(1, 1), 1);
        $Items->Links->setId(1);
        $Items->Links->postAction(Action::Create, array('targetEntityType' => 'items'));
        // now import this in our experiment like if we click the import links button
        $Links = new Links($this->Experiments, $Items->id);
        $this->assertIsInt($Links->postAction(Action::Duplicate, array('targetEntityType' => 'items')));
    }

    public function testPatch(): void
    {
        $this->assertIsArray($this->Experiments->Links->patch(Action::Duplicate, array()));
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->Experiments->Links->readOne());
    }

    public function testReadRelated(): void
    {
        $this->assertIsArray($this->Experiments->Links->readRelated());
    }
}
