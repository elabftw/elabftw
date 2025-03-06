<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Exception;
use Override;

/**
 * This exception contains a description and is suitable for errors sent in JSON, where we provide a description, for instance through API response
 */
final class WithDescriptionException extends WithMessageException
{
    public function __construct(string $message, private string $description, int $code, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->description;
    }
}
