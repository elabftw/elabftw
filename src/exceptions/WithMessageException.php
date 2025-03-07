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
 * Base class for exceptions with customizable message
 */
class WithMessageException extends Exception
{
    public function __construct(string $message, int $code, ?Exception $previous = null, private ?string $description = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getDescription(): string
    {
        return $this->description ?? $this->getMessage();
    }
}
