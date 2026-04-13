<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Override;

/**
 * Evidency uses a request header with an api key for auth, so we use that instead of Basic auth
 */
final class EvidencyTimestampUtils extends TimestampUtils
{
    #[Override]
    protected function addAuthToRequest(array $options): array
    {
        $options['headers']['X-API-Key'] = $this->tsConfig['ts_password'];
        return $options;
    }
}
