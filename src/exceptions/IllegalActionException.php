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

use Elabftw\Elabftw\Tools;
use Exception;

/**
 * For errors that are suspicious (request has been edited for instance)
 */
final class IllegalActionException extends ImproperActionException
{
    public function __construct(?string $message = null, int $code = 0, ?Exception $previous = null)
    {
        if ($message === null) {
            $message = Tools::error(true);
        }
        parent::__construct($message, $code, $previous);
    }
}
