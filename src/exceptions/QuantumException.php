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
 * Throw this if you don't want to disclose if a resource exists
 * Prevent enumeration and information leakage by having an exception that can be both error and success
 */
class QuantumException extends Exception
{
    public function __construct(string $message, int $code = 42, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
