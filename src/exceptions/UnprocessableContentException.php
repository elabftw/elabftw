<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @author    Moustapha Camara <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;

/**
 * The request is correct but the validation fails due to internal constraint
 * see https://datatracker.ietf.org/doc/html/rfc9110#status.422
 */
class UnprocessableContentException extends Exception
{
    public function __construct(string $message, int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
