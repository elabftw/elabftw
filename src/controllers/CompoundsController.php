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
use Elabftw\Models\Config;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;

class CompoundsController extends AbstractHtmlController
{
    protected function getTemplate(): string
    {
        return 'compounds.html';
    }

    protected function getData(): array
    {
        $Config = Config::getConfig();
        $httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
        $Compounds = new Compounds($httpGetter, $this->app->Users);
        return array(
            'compoundsArr' => $Compounds->readAll($Compounds->getQueryParams($this->app->Request->query)),
        );
    }
}
