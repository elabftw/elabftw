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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Check;

/**
 * For things that have an id that we can set
 */
trait SetIdTrait
{
    public ?int $id;

    /**
     * Check and set id
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
    }
}
