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

use Elabftw\Models\ItemsTypes;

use function array_merge;

class CompoundsController extends AbstractHtmlController
{
    protected function getTemplate(): string
    {
        return 'compounds.html';
    }

    protected function getPageTitle(): string
    {
        return _('Compounds');
    }

    protected function getData(): array
    {
        return array_merge(
            parent::getData(),
            array(
                'resourceCategoriesArr' => (new ItemsTypes($this->app->Users))->readAll(),
            ),
        );
    }
}
