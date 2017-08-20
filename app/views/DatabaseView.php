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
    /** @var Database $Entity the database entity */
    public $Entity;

    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->Entity = $entity;
    }
}
