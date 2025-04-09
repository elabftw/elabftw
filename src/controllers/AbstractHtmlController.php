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
use Override;

abstract class AbstractHtmlController implements ControllerInterface
{
    public function __construct(protected App $app) {}

    #[Override]
    public function getResponse(): Response
    {
        $Response = new Response();
        $Response->prepare($this->app->Request);
        $Response->setContent($this->app->render($this->getTemplate(), $this->getData()));

        return $Response;
    }

    abstract protected function getPageTitle(): string;

    abstract protected function getTemplate(): string;

    protected function getData(): array
    {
        return array(
            'pageTitle' => $this->getPageTitle(),
        );
    }
}
