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

use Elabftw\Traits\TestsUtilsTrait;

use function count;

class PinsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private Items $Items;

    private Templates $Templates;

    private Pins $Pins;

    protected function setUp(): void
    {
        $this->Experiments = $this->getFreshExperiment();
        $this->Items = $this->getFreshItem();
        $this->Templates = $this->getFreshTemplate();
        $this->Pins = new Pins($this->Experiments);
    }

    public function testTogglePin(): void
    {
        $this->Pins->togglePin();
        $this->assertTrue($this->Pins->isPinned());
        $this->assertCount(1, $this->Pins->readAll());
        $this->Pins->togglePin();
        $this->assertCount(0, $this->Pins->readAll());

        $Pins = new Pins($this->Items);
        $Pins->togglePin();
        $this->assertTrue($Pins->isPinned());
        $this->assertCount(1, $Pins->readAll());
        $Pins->togglePin();
        $this->assertCount(0, $Pins->readAll());

        $Pins = new Pins($this->Templates);
        $this->assertFalse($Pins->isPinned());
        $this->assertTrue(count($Pins->readAll()) === 0);
        $Pins->togglePin();
        $this->assertTrue($Pins->isPinned());
        $this->assertTrue(count($Pins->readAll()) === 1);
    }

    public function testDuplicateIsNotPinned(): void
    {
        $this->checkDuplicateIsNotPinned($this->Experiments);
        $this->checkDuplicateIsNotPinned($this->Items);
    }

    public function testTemplateIsAlwaysPinnedWhenCreated(): void
    {
        $fresh = $this->duplicateEntity($this->Templates);
        $this->assertFalse(new Pins($fresh)->isPinned());
    }

    private function checkDuplicateIsNotPinned(Experiments | Items $entity): void
    {
        $fresh = $this->duplicateEntity($entity);
        $this->assertFalse(new Pins($fresh)->isPinned());
    }

    private function duplicateEntity(AbstractEntity $entity): AbstractEntity
    {
        $newId = $entity->duplicate();
        $fresh = clone $entity;
        $fresh->setId($newId);

        return $fresh;
    }
}
