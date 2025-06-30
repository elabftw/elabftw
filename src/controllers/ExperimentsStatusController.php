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

use Elabftw\Models\ExperimentsStatus;
use Override;

/**
 * For experiments-status.php
 */
final class ExperimentsStatusController extends AbstractStatusController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'experiments-status.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Experiments status');
    }

    #[Override]
    protected function getModel(): ExperimentsStatus
    {
        return new ExperimentsStatus($this->app->Teams);
    }
}
