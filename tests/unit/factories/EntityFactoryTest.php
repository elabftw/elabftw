<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Factories;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
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
        $factory = new EntityFactory($Users, AbstractEntity::TYPE_EXPERIMENTS);
        $this->assertInstanceOf(Experiments::class, $factory->getEntity());
        $factory = new EntityFactory($Users, AbstractEntity::TYPE_ITEMS);
        $this->assertInstanceOf(Items::class, $factory->getEntity());
        $factory = new EntityFactory($Users, AbstractEntity::TYPE_TEMPLATES);
        $this->assertInstanceOf(Templates::class, $factory->getEntity());
        $factory = new EntityFactory($Users, AbstractEntity::TYPE_ITEMS_TYPES);
        $this->assertInstanceOf(ItemsTypes::class, $factory->getEntity());
        $factory = new EntityFactory($Users, 'kenobi');
        $this->expectException(ImproperActionException::class);
        $factory->getEntity();
    }
}
