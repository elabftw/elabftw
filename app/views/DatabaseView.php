<?php
/**
 * \Elabftw\Elabftw\DatabaseView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Database View
 */
class DatabaseView extends EntityView
{
    /**
     * Constructor
     *
     * @param Database $entity
     */
    public function __construct(Database $entity)
    {
        $this->Entity = $entity;
    }
}
