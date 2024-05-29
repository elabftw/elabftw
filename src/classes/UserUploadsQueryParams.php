<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\Orderby;

/**
 * Default query params for UserUploads
 */
final class UserUploadsQueryParams extends BaseQueryParams
{
    public Orderby $orderby = Orderby::CreatedAt;

    public int $limit = 42;
}
