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
use Elabftw\Models\Templates;

/**
 * For experiments.php
 */
class ExperimentsController extends AbstractEntityController
{
    public function __construct(App $app, Experiments | Templates $entity)
    {
        parent::__construct($app, $entity);

        $Teams = new Teams($this->App->Users, $this->App->Users->team);
        $Category = new ExperimentsCategories($Teams);
        $this->categoryArr = $Category->readAll();
        $Status = new ExperimentsStatus($Teams);
        $this->statusArr = $Status->readAll();
    }

    protected function getPageTitle(): string
    {
        if ($this->Entity instanceof Experiments) {
            return ngettext('Experiment', 'Experiments', 2);
        }
        return _('Experiment templates');
    }
}
