<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Override;

/**
 * RFC3161 timestamping with a custom TSA
 */
final class MakeCustomTimestamp extends AbstractMakeTrustedTimestamp
{
    /** default hash algo for file */
    private const string TS_HASH = 'sha256';

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    #[Override]
    public function getTimestampParameters(): array
    {
        $config = $this->configArr;

        $password = '';
        if (($config['ts_password'] ?? '') !== '') {
            $password = Crypto::decrypt($config['ts_password'], Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        }

        $hash = $config['ts_hash'];
        $allowedAlgos = array('sha256', 'sha384', 'sha512');
        if (!in_array($hash, $allowedAlgos, true)) {
            $hash = self::TS_HASH;
        }

        return array(
            'ts_login' => $config['ts_login'],
            'ts_password' => $password,
            'ts_url' => $config['ts_url'],
            'ts_cert' => $config['ts_cert'],
            'ts_hash' => $hash,
            'ts_chain' => '/etc/ssl/cert.pem',
        );
    }
}
