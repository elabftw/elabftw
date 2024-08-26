<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

trait TestsUtilsTrait
{
    protected function getFreshExperiment(): Experiments
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItem(): Items
    {
        $Entity = new Items(new Users(1, 1));
        $id = $Entity->create(template: 1);
        $Entity->setId($id);
        return $Entity;
    }
}
