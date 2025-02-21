<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Override;

/**
 * RFC3161 timestamping with Sectigo timestamping service
 * https://sectigo.com/resource-library/time-stamping-server
 */
final class MakeSectigoTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'http://timestamp.sectigo.com/';

    protected const string TS_HASH = 'sha256';

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    #[Override]
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
