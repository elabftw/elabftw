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

/**
 * This factory is responsible for providing an entity
 */
class EntityFactory
{
    public function __construct(private Users $users, private string $type, private ?int $itemId = null)
    {
    }

    public function getEntity(): AbstractEntity
    {
        switch ($this->type) {
            case AbstractEntity::TYPE_EXPERIMENTS:
                return new Experiments($this->users, $this->itemId);
            case AbstractEntity::TYPE_ITEMS:
                return new Items($this->users, $this->itemId);
            case AbstractEntity::TYPE_TEMPLATES:
                return new Templates($this->users, $this->itemId);
            case AbstractEntity::TYPE_ITEMS_TYPES:
                return new ItemsTypes($this->users, $this->itemId);
            default:
                throw new ImproperActionException('Incorrect entity type');
        }
    }
}
