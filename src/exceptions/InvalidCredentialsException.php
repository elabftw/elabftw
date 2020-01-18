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
 * Throw this if the SQL query failed
 */
class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        $message = _('Invalid email/password combination.');
        parent::__construct($message, 0);
    }
}
