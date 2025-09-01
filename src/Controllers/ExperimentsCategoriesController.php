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

use Override;

/**
 * For experiments-categories.php
 */
final class ExperimentsCategoriesController extends AbstractHtmlController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'experiments-categories.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Experiments categories');
    }
}
