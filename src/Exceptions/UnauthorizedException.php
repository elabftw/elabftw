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

use Elabftw\Enums\Messages;
use Exception;

/**
 * If user is not authorized to access this resource
 */
final class UnauthorizedException extends AppException
{
    public function __construct(?string $message = null, int $code = 401, ?Exception $previous = null)
    {
        if ($message === null) {
            $message = Messages::UnauthorizedError->toHuman();
        }
        parent::__construct($message, $code, $previous);
    }

    public function getDescription(): string
    {
        return $this->getMessage();
    }
}
