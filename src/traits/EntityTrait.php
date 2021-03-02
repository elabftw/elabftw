<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
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

    public array $filters = array();

    protected Db $Db;

    /**
     * Check and set id
     */
    public function setId(int $id): void
    {
        if (Check::id($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }
        $this->id = $id;
        // prevent reusing of old data from previous id
        $this->entityData = array();
        $this->filters = array();
    }
}
