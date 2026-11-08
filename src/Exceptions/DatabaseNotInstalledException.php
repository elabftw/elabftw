<?php

/**
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\Messages;
use Exception;

/**
 * Throw this if the database is reachable but its structure is not installed yet
 */
final class DatabaseNotInstalledException extends AppException
{
    protected Messages $error = Messages::DatabaseError;

    public function __construct(?Exception $previous = null)
    {
        parent::__construct('Database is not yet installed. Run bin/init db:install.', previous: $previous);
    }
}
