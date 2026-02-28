<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi ~ Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\EntityType;
use Elabftw\Traits\TestsUtilsTrait;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testCreatedFrom(): void
    {
        foreach (EntityType::cases() as $entityType) {
            $this->createdFrom($entityType);
        }
    }

    private function createdFrom(EntityType $entityType): void
    {
        $entity = $entityType->toInstance($this->getUserInTeam(1));
        $id = $entity->create();
        $entity->setId($id);
        $this->assertNull($entity->entityData['created_from_type']);
        $this->assertNull($entity->entityData['created_from_id']);
        // now create from template
        $tplType = $entityType->asTemplateTypeOrNull();
        $tplId = $tplType === null ? null : 12;
        $new = $entity->create(createdFromType: $tplType, createdFromId: $tplId);
        $entity->setId($new);
        $this->assertSame($entityType->asTemplateTypeOrNull()?->toInt(), $entity->entityData['created_from_type']);
        $this->assertSame($tplId, $entity->entityData['created_from_id']);
    }
}
