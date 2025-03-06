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

use Override;

/**
 * Throw this if the api key is not good
 */
final class InvalidApiKeyException extends UnauthorizedException
{
    public function __construct()
    {
        parent::__construct('Unauthorized', 401);
    }

    #[Override]
    public function getDescription(): string
    {
        return _('No corresponding API key found!');
    }
}
