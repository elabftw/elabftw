<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Exceptions;

use function dirname;
use Exception;
use function file_get_contents;

/**
 * When the database schema is wrong.
 */
class InvalidSchemaException extends Exception
{
    /**
     * The message will always be the same here
     */
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        $message = file_get_contents(dirname(__DIR__) . '/templates/invalid-schema.html');
        if ($message === false) {
            $message = 'Run the bin/console db:update command to finish the update!';
        }
        parent::__construct($message, $code, $previous);
    }
}
