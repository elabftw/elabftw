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

    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Items = new Items($this->Users, 1);
        $this->Templates = new Templates($this->Users, 1);
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

    /**
     * ensure that duplicated entry is not pinned
     * @return void
     */
    public function testDuplicateIsNotPinned(): void
    {
        // duplicate existing experiment
        $source = $this->Experiments;
        $duplicate = $source->duplicate();
        $freshSource = new Experiments($this->Users, $duplicate);
        $this->assertNotEquals($source->entityData['id'], $freshSource->entityData['id']);

        // ensure the duplicate experiment is not pinned
        $this->assertCount(0, $freshSource->Pins->readAll());
        $this->assertCount(0, $freshSource->Pins->readAllSimple());

        // duplicate existing item
        $sourceItem = $this->Items;
        $duplicateItem = $sourceItem->duplicate();
        $freshItem = new Items($this->Users, $duplicateItem);
        $this->assertNotEquals($sourceItem->entityData['id'], $freshItem->entityData['id']);

        // ensure the duplicate item is not pinned
        $this->assertCount(0, $freshItem->Pins->readAll());
        $this->assertCount(0, $freshItem->Pins->readAllSimple());
    }

    /**
     * ensure that duplicating a pinned/non-pinned template results in a new pinned template in both cases
     * @return void
     */
    public function testTemplateIsAlwaysPinnedWhenCreated(): void
    {
        // create a new template and ensure it is pinned
        $source = new Templates($this->Users, 1);
        $this->assertTrue($source->Pins->isPinned());
        $this->assertTrue(count($source->Pins->readAll()) > 0);

        // duplicate the template
        $duplicate = $source->duplicate();
        $fresh = new Templates($this->Users, $duplicate);
        $this->assertNotEquals($source->entityData['id'], $fresh->entityData['id']);

        // ensure the duplicate is pinned on creation
        $this->assertTrue($fresh->Pins->isPinned());
    }
}
