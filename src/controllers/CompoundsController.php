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

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\CompoundsQueryParams;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Compounds;
use Symfony\Component\HttpFoundation\Response;

class CompoundsController implements ControllerInterface
{
    public function __construct(protected App $app) {}

    public function getResponse(): Response
    {
        $template = 'compounds.html';
        $Compounds = new Compounds($this->app->Users);

        $Response = new Response();
        $Response->prepare($this->app->Request);
        $queryParams = new CompoundsQueryParams($this->app->Request->query);
        $Response->setContent($this->app->render($template, array(
            'compoundsArr' => $Compounds->readAll($queryParams),
        )));

        return $Response;
    }
}
