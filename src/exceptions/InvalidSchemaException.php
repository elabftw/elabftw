<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;

/**
 * When the database schema is wrong.
 */
class InvalidSchemaException extends Exception
{
    /**
     * The message will always be the same here
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $message = 'Database schema is different from required schema. Please read documentation/release notes to update the database.';
        parent::__construct($message, $code, $previous);
    }
}
