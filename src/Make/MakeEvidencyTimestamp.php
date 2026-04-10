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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\EvidencyTimestampUtils;
use GuzzleHttp\Client;
use Override;

/**
 * RFC3161 timestamping with Evidency service
 * https://docs.evidency.io/docs/timestamping-service#timestamp-rfc3161-request
 */
class MakeEvidencyTimestamp extends AbstractMakeTrustedTimestamp
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

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    #[Override]
    public function getTimestampParameters(): array
    {
        $config = $this->configArr;

        // here the ts_login corresponds to the project ID
        if (empty($config['ts_login'])) {
            throw new ImproperActionException('Evidency timestamping requires a project ID!');
        }

        // here the ts_password corresponds to the API key
        if (empty($config['ts_password'])) {
            throw new ImproperActionException('Evidency timestamping requires an api key!');
        }

        return array(
            'ts_login' => $config['ts_login'],
            'ts_password' => $config['ts_password'],
            // use static here so the dev class ts_url override is taken into account
            'ts_url' => $this->getTsUrl(),
            'ts_hash' => self::TS_HASH,
            // no need to verify for this provider
            'ts_cert' => '',
            'ts_chain' => '',
        );
    }

    private function getTsUrl(): string
    {
        return sprintf(static::TS_URL, $this->configArr['ts_login']);
    }
}
