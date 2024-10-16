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

use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;

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

    public function testDuplicateIsNotPinned(): void
    {
        $this->checkDuplicateIsNotPinned($this->Experiments);
        $this->checkDuplicateIsNotPinned($this->Items);
    }

    public function testTemplateIsAlwaysPinnedWhenCreated(): void
    {
        // Confirm the created template is pinned
        $this->assertTrue($this->Templates->Pins->isPinned());
        $this->assertTrue(count($this->Templates->Pins->readAll()) > 0);

        // Duplicate the template and ensure it is pinned on creation
        $fresh = $this->duplicateEntity($this->Templates);
        $this->assertNotEquals($this->Templates->id, $fresh->id);
        $this->assertTrue($fresh->Pins->isPinned());
        $this->assertTrue(count($fresh->Pins->readAll()) > 0);
    }

    private function checkDuplicateIsNotPinned(Experiments | Items $entity): void
    {
        $fresh = $this->duplicateEntity($entity);
        $this->assertNotEquals($entity->id, $fresh->id);
        $this->assertCount(0, $fresh->Pins->readAll());
        $this->assertCount(0, $fresh->Pins->readAllSimple());
    }

    private function duplicateEntity(Experiments | Items | Templates $entity): Experiments | Items | Templates
    {
        $duplicated = $entity->duplicate();

        $fresh = match ($entity->entityType) {
            EntityType::Experiments => new Experiments($this->Users, $duplicated),
            EntityType::Items => new Items($this->Users, $duplicated),
            EntityType::Templates => new Templates($this->Users, $duplicated),
            default => null
        };

        if ($fresh === null) {
            $message = sprintf('Invalid entity type: %s', $entity->entityType->value);
            throw new ImproperActionException($message);
        }

        return $fresh;
    }
}
