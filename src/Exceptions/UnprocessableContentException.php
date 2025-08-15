<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;

/**
 * The request is correct but the validation fails due to internal constraint
 * see https://datatracker.ietf.org/doc/html/rfc9110#status.422
 */
final class UnprocessableContentException extends AppException
{
    public function __construct(string $message, int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
