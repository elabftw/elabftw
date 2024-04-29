<?php

/**
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;

/**
 * Throw this if the SQL query failed
 */
final class DatabaseErrorException extends Exception
{
    public function __construct(?Exception $previous = null)
    {
        parent::__construct(_('Something went wrong! Try refreshing the page.'), 515, $previous);
    }
}
