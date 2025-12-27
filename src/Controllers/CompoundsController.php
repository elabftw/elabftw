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

use Override;

final class CompoundsController extends AbstractHtmlController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'compounds.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Compounds');
    }
}
