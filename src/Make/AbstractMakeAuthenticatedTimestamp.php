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

use Elabftw\Params\Guard;
use Override;

/**
 * For timestamp services that require a login/password
 */
abstract class AbstractMakeAuthenticatedTimestamp extends AbstractMakeTrustedTimestamp
{
    #[Override]
    protected function getLogin(): string
    {
        return Guard::getNonEmptyStringValueOfRequiredParam('ts_login', $this->configArr);
    }

    #[Override]
    protected function getPassword(): string
    {
        return Guard::getNonEmptyStringValueOfRequiredParam('ts_password', $this->configArr);
    }
}
