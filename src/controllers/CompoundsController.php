<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Models\Compounds;

class CompoundsController extends AbstractHtmlController
{
    protected function getTemplate(): string
    {
        return 'compounds.html';
    }

    protected function getData(): array
    {
        $Compounds = new Compounds($this->app->Users);
        return array(
            'compoundsArr' => $Compounds->readAll($Compounds->getQueryParams($this->app->Request->query)),
        );
    }
}
