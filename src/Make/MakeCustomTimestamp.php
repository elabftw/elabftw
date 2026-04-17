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

use Elabftw\Params\Guard;
use Override;

use function in_array;

/**
 * RFC3161 timestamping with a custom TSA
 */
final class MakeCustomTimestamp extends AbstractMakeTrustedTimestamp
{
    #[Override]
    protected function getPassword(): string
    {
        $password = '';
        if (($this->configArr['ts_password'] ?? '') !== '') {
            $password = $this->configArr['ts_password'];
        }
        return $password;
    }

    #[Override]
    protected function getUrl(): string
    {
        return Guard::getNonEmptyStringValueOfRequiredParam('ts_url', $this->configArr);
    }

    #[Override]
    protected function getChain(): string
    {
        return '/etc/ssl/cert.pem';
    }

    #[Override]
    protected function getHash(): string
    {
        $hash = $this->configArr['ts_hash'];
        if (!in_array($hash, self::ALLOWED_HASH_ALGOS, true)) {
            return self::TS_HASH;
        }
        return $hash;
    }
}
