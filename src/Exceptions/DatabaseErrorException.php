<?php

/**
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\Messages;

/**
 * Throw this if the SQL query failed
 */
final class DatabaseErrorException extends AppException
{
    protected Messages $error = Messages::DatabaseError;

    private readonly string $sqlstate;

    private readonly int $errorCode;

    private readonly string $errorMessage;

    public function __construct(array $errorInfo)
    {
        $this->sqlstate = $errorInfo[0];
        $this->errorCode = (int) $errorInfo[1];
        $this->errorMessage = $errorInfo[2] ?? '';
        parent::__construct($this->errorMessage, $this->errorCode);
    }

    public function getSqlstate(): string
    {
        return $this->sqlstate;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
