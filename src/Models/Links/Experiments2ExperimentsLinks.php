<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Links;

use Override;

/**
 * All about Experiments Links in Experiments
 */
final class Experiments2ExperimentsLinks extends AbstractExperimentsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'experiments2experiments';
    }

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return 'experiments2items';
    }
}
