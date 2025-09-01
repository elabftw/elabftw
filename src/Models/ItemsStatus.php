<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Override;

/**
 * Status for items/resources
 */
final class ItemsStatus extends AbstractStatus
{
    protected string $table = 'items_status';

    #[Override]
    protected function getUsersCanwriteName(): string
    {
        return 'resources_status';
    }
}
