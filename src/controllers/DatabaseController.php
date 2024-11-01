<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;

/**
 * For database.php
 */
class DatabaseController extends AbstractEntityController
{
    public function __construct(App $app, Items $entity)
    {
        parent::__construct($app, $entity);

        $Category = new ItemsTypes($this->App->Users);
        $queryParams = $Category->getQueryParams($this->App->Request->query);
        $this->categoryArr = $Category->readAll($queryParams);
        $Status = new ItemsStatus(new Teams($this->App->Users, $this->App->Users->team));
        $queryParams = $Status->getQueryParams($this->App->Request->query);
        $this->statusArr = $Status->readAll($queryParams);
    }
}
