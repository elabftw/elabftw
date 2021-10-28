<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

/**
 * RFC3161 timestamping with Sectigo timestamping service
 * https://sectigo.com/resource-library/time-stamping-server
 */
class MakeSectigoTimestamp extends MakeTimestamp
{
    protected const TS_URL = 'http://timestamp.sectigo.com/';

    protected const TS_HASH = 'sha256';

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    public function getTimestampParameters(): array
    {
        return array(
            'ts_login' => '',
            'ts_password' => '',
            'ts_url' => self::TS_URL,
            'ts_hash' => self::TS_HASH,
            'ts_cert' => '',
            'ts_chain' => '',
            );
    }
}
