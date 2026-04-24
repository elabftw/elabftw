<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Override;

final class InterfaceController extends AbstractHtmlController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'interface.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return 'Interface elements';
    }
}
