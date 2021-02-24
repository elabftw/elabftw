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
 * If user is not authorized to access this resource
 */
class UnauthorizedException extends Exception
{
    /**
     * Redefine the exception so message isn't optional
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        if ($message === null) {
            $message = _('Authentication required');
        }
        parent::__construct($message, $code, $previous);
    }
}
