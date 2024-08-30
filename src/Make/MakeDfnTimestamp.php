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

use function dirname;

/**
 * RFC3161 timestamping with the free to use DFN timestamping service
 * https://www.pki.dfn.de/faqpki/faq-zeitstempel/
 */
class MakeDfnTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'http://zeitstempel.dfn.de';

    protected const string TS_CERT = 'dfn.pem';

    protected const string TS_CHAIN = 'dfn-chain.pem';

    protected const string TS_HASH = 'sha256';

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
            'ts_cert' => dirname(__DIR__) . '/certs/' . self::TS_CERT,
            'ts_hash' => self::TS_HASH,
            'ts_chain' => dirname(__DIR__) . '/certs/' . self::TS_CHAIN,
        );
    }
}
