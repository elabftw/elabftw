<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

/**
 * ImmutableComments are used to convey information about timestamping or signature
 * They cannot be modified or removed
 */
class ImmutableComments extends Comments
{
    protected int $immutable = 1;
}
