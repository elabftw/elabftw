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
 * Throw this if the auth is not good
 */
class InvalidCredentialsException extends Exception
{
    public function __construct(?string $message = null)
    {
        if ($message === null) {
            $message = _('Invalid email/password combination.');
        }
        parent::__construct($message, 0);
    }
}
