<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Check;

/**
 * For things that are used by experiments, database, status, item types, templates, …
 *
 */
trait EntityTrait
{
    public ?int $id;

    public array $entityData = array();

    protected Db $Db;

    protected string $filterSql = '';

    abstract public function readOne(): array;

    /**
     * Check and set id; populate the data object
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
        if ($id === null) {
            return;
        }
        if (Check::id($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }
        // this will load it in entityData
        $this->readOne();
        // clear out filters
        $this->filterSql = '';
    }
}
