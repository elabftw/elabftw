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
use Elabftw\Interfaces\ControllerInterface;
use Symfony\Component\HttpFoundation\Response;

class ChemEditorController implements ControllerInterface
{
    public function __construct(protected App $app) {}

    public function getResponse(): Response
    {
        $template = 'chem-editor.html';

        $Response = new Response();
        $Response->prepare($this->app->Request);
        $Response->setContent($this->app->render($template, array()));

        return $Response;
    }
}
