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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Elabftw\Exceptions\ImproperActionException;
use Override;

/**
 * RFC3161 timestamping with Universign service
 * https://www.universign.com/en/
 */
class MakeUniversignTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'https://ws.universign.eu/tsa';

    protected const string TS_HASH = 'sha256';

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
            throw new ImproperActionException('Universign timestamping requires a login!');
        }

        if (empty($config['ts_password'])) {
            throw new ImproperActionException('Universign timestamping requires a password!');
        }
        $password = Crypto::decrypt($config['ts_password'], Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));

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
