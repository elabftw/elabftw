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
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;

/**
 * This factory is responsible for providing an entity
 */
class EntityFactory
{
    public function __construct(private Users $users, private EntityType $type, private ?int $itemId = null)
    {
    }

    public function getEntity(): AbstractEntity
    {
        return match ($this->type) {
            EntityType::Experiments => new Experiments($this->users, $this->itemId),
            EntityType::Items => new Items($this->users, $this->itemId),
            EntityType::Templates => new Templates($this->users, $this->itemId),
            EntityType::ItemsTypes => new ItemsTypes($this->users, $this->itemId),
        };
    }
}
