<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
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
        $message = '<h1>Almost there!</h1><h2>To finish the update, run the "bin/console db:update" command. For Docker users that would be:<br><pre>docker exec -it elabftw bin/console db:update</pre></h2>See <a href="https://doc.elabftw.net/how-to-update.html">documentation</a>.';
        parent::__construct($message, $code, $previous);
    }
}
