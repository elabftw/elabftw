<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

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
            case 'experiments':
            // FIXME should be only one way to express this
            case 'experiment':
                return new Experiments($this->users, $this->itemId);
            case 'items':
            case 'item':
                return new Items($this->users, $this->itemId);
            case 'experiments_templates':
            case 'template':
                return new Templates($this->users, $this->itemId);
            case 'items_types':
            case 'itemtype':
                return new ItemsTypes($this->users, $this->itemId);
            default:
                throw new ImproperActionException('Incorrect entity type');
        }
    }
}
