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
use Elabftw\Models\Templates;
use Override;

/**
 * For experiments.php
 */
final class ExperimentsController extends AbstractEntityController
{
    public function __construct(App $app, Experiments | Templates $entity)
    {
        parent::__construct($app, $entity);

        $this->categoryArr = $app->experimentsCategoryArr;
        $this->statusArr = $this->experimentsStatusArr;
    }

    #[Override]
    protected function getPageTitle(): string
    {
        if ($this->Entity instanceof Experiments) {
            return ngettext('Experiment', 'Experiments', 2);
        }
        return _('Experiment templates');
    }
}
