<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Factories;

use Elabftw\Enums\EntityType;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;

class EntityFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntity(): void
    {
        $Users = new Users(1, 1);
        $factory = new EntityFactory($Users, EntityType::Experiments);
        $this->assertInstanceOf(Experiments::class, $factory->getEntity());
        $factory = new EntityFactory($Users, EntityType::Items);
        $this->assertInstanceOf(Items::class, $factory->getEntity());
        $factory = new EntityFactory($Users, EntityType::Templates);
        $this->assertInstanceOf(Templates::class, $factory->getEntity());
        $factory = new EntityFactory($Users, EntityType::ItemsTypes);
        $this->assertInstanceOf(ItemsTypes::class, $factory->getEntity());
    }
}
