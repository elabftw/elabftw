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

class ChemEditorController extends AbstractHtmlController
{
    protected function getTemplate(): string
    {
        return 'chem-editor.html';
    }

    protected function getData(): array
    {
        return array(
            'resourceCategoriesArr' => (new ItemsTypes($this->app->Users))->readAll(),
        );
    }
}
