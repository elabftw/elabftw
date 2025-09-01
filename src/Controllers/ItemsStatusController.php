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

use Elabftw\Models\ItemsStatus;
use Override;

/**
 * For resources-status.php
 */
final class ItemsStatusController extends AbstractStatusController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'resources-status.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Resources status');
    }

    #[Override]
    protected function getModel(): ItemsStatus
    {
        return new ItemsStatus($this->app->Teams);
    }
}
