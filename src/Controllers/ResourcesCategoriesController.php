<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Override;

/**
 * For resources-categories.php
 */
final class ResourcesCategoriesController extends AbstractHtmlController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'resources-categories.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Resources categories');
    }
}
