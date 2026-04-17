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

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Services\EvidencyTimestampUtils;
use GuzzleHttp\Client;
use Override;

use function sprintf;

/**
 * RFC3161 timestamping with Evidency service
 * https://docs.evidency.io/docs/timestamping-service#timestamp-rfc3161-request
 */
class MakeEvidencyTimestamp extends AbstractMakeAuthenticatedTimestamp
{
    protected const string TS_URL = 'https://api.evidency.io/v3/projects/%s/timestamp';

    protected const string TS_HASH = 'sha512';

    #[Override]
    public function getTimestampUtils(): EvidencyTimestampUtils
    {
        return new EvidencyTimestampUtils(
            new Client(),
            $this->generateData(),
            $this->getTimestampParameters(),
            new TimestampResponse(),
        );
    }

    #[Override]
    protected function getUrl(): string
    {
        // we use the project id (ts_login) in the URL
        return sprintf(static::TS_URL, $this->configArr['ts_login']);
    }
}
