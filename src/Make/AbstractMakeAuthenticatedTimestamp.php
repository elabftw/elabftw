<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Exceptions\ImproperActionException;
use Override;

/**
 * For timestamp services that require a login/password
 */
abstract class AbstractMakeAuthenticatedTimestamp extends AbstractMakeTrustedTimestamp
{
    #[Override]
    protected function getLogin(): string
    {
        if (empty($this->configArr['ts_login'])) {
            throw new ImproperActionException('Login value for timestamp service is not set!');
        }
        return $this->configArr['ts_login'];
    }

    #[Override]
    protected function getPassword(): string
    {
        if (empty($this->configArr['ts_password'])) {
            throw new ImproperActionException('Login value for timestamp service is not set!');
        }
        return $this->configArr['ts_password'];
    }
}
