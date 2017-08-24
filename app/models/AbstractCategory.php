<?php
/**
 * \Elabftw\Elabftw\AbstractCategory
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * A category is a status for experiments and item type for db item
 */
abstract class AbstractCategory implements CrudInterface
{
    /**
     * SQL to get all categories for current team
     *
     * @return array all the items types for the team
     */
    abstract public function readAll();

    /**
     * Get the color of an item type
     *
     * @param int $id ID of the category
     * @return string
     */
    abstract public function readColor($id);

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    abstract protected function countItems($id);

    /**
     * Destroy a category
     *
     * @param int $id
     * @return bool
     */
    abstract public function destroy($id);
}
