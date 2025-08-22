<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @author    Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Exceptions;

use Elabftw\Enums\Messages;

/**
 * Throw this if the MFA code verification failed
 */
final class InvalidMfaCodeException extends AppException
{
    public function __construct()
    {
        parent::__construct(Messages::InvalidAuthenticationCode->toHuman(), Messages::InvalidAuthenticationCode->toHttpCode());
    }
}
