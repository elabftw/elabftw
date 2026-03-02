<?php

/**
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\Messages;

/**
 * Throw this if the requested resource cannot be found
 */
final class ResourceNotFoundException extends AppException
{
    protected Messages $error = Messages::ResourceNotFound;
}
