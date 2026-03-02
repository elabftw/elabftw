<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Exceptions\ImproperActionException;
use Override;

/**
 * RFC3161 timestamping with DGN service
 * https://www.dgn.de/dgn-zeitstempeldienst/
 */
final class MakeDgnTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'https://zeitstempel.dgn.de/tss';

    protected const string TS_HASH = 'sha512';

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    #[Override]
    public function getTimestampParameters(): array
    {
        $config = $this->configArr;

        if (empty($config['ts_login'])) {
            throw new ImproperActionException('DGN timestamping requires a login!');
        }

        if (empty($config['ts_password'])) {
            throw new ImproperActionException('DGN timestamping requires a password!');
        }
        $password = $config['ts_password'];

        return array(
            'ts_login' => $config['ts_login'],
            'ts_password' => $password,
            // use static here so the dev class ts_url override is taken into account
            'ts_url' => static::TS_URL,
            'ts_hash' => self::TS_HASH,
            // no need to verify for this provider
            'ts_cert' => '',
            'ts_chain' => '',
        );
    }
}
