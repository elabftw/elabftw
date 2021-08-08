<?php declare(strict_types=1);
/**
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Exceptions;

use Exception;

/**
 * Throw this if the SQL query failed
 */
final class DatabaseErrorException extends Exception
{
    public function __construct(?Exception $previous = null)
    {
        parent::__construct('An error occured during the execution of the SQL query.', 515, $previous);
    }
}
