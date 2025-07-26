<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Models\AbstractStatus;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * For {experiments|resources}-status.php
 */
abstract class AbstractStatusController extends AbstractHtmlController
{
    abstract protected function getModel(): AbstractStatus;

    #[Override]
    protected function getData(): array
    {
        $Status = $this->getModel();

        return array_merge(
            parent::getData(),
            array(
                'statusArr' => $Status->readAll($Status->getQueryParams(new InputBag(array('limit' => 9999)))),
            ),
        );
    }
}
