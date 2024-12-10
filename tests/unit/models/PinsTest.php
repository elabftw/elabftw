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

class PinsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Items $Items;

    private Templates $Templates;

    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $this->Experiments = new Experiments($Users, 1);
        $this->Items = new Items($Users, 1);
        $this->Templates = new Templates($Users, 1);
    }

    public function testTogglePin(): void
    {
        $this->Experiments->Pins->togglePin();
        $this->assertTrue($this->Experiments->Pins->isPinned());
        $this->assertCount(1, $this->Experiments->Pins->readAll());
        $this->Experiments->Pins->togglePin();
        $this->assertCount(0, $this->Experiments->Pins->readAll());
        $this->assertCount(0, $this->Experiments->Pins->readAllSimple());

        $this->Items->Pins->togglePin();
        $this->assertTrue($this->Items->Pins->isPinned());
        $this->assertCount(1, $this->Items->Pins->readAll());
        $this->Items->Pins->togglePin();
        $this->assertCount(0, $this->Items->Pins->readAll());

        $this->assertFalse($this->Templates->Pins->isPinned());
        // There is already one template from TemplatesTest
        $this->assertTrue(count($this->Templates->Pins->readAll()) > 1);
        $this->Templates->Pins->togglePin();
        $this->assertTrue($this->Templates->Pins->isPinned());
        $this->assertTrue(count($this->Templates->Pins->readAll()) > 0);
    }
}
