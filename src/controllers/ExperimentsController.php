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
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Teams;

/**
 * For experiments.php
 */
class ExperimentsController extends AbstractEntityController
{
    /**
     * Constructor
     */
    public function __construct(App $app, Experiments $entity)
    {
        parent::__construct($app, $entity);

        $Teams = new Teams($this->App->Users, $this->App->Users->team);
        $Category = new ExperimentsCategories($Teams);
        $queryParams = $Category->getQueryParams($this->App->Request->query);
        $this->categoryArr = $Category->readAll($queryParams);
        $Status = new ExperimentsStatus($Teams);
        $queryParams = $Status->getQueryParams($this->App->Request->query);
        $this->statusArr = $Status->readAll($queryParams);
    }
}
