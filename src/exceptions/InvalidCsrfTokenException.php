<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Exceptions;

use Exception;

/**
 * When the CSRF token doesn't validate
 */
final class InvalidCsrfTokenException extends Exception
{
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        $message = _('Your session expired.');
        parent::__construct($message, $code, $previous);
    }
}
