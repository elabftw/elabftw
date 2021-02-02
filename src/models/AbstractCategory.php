<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SortableTrait;

/**
 * A category is a status for experiments and item type for db item
 */
abstract class AbstractCategory implements CrudInterface
{
    use SortableTrait;

    protected Db $Db;

    protected Users $Users;

    /**
     * Get the color of an item type
     */
    abstract public function readColor(int $id): string;

    /**
     * Get all the things
     */
    abstract public function readAll(): array;

    /**
     * Count all items of this type
     */
    abstract protected function countItems(int $id): int;
}
