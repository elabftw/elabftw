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

use function dirname;

/**
 * RFC3161 timestamping with the free to use DFN timestamping service
 * https://www.pki.dfn.de/faqpki/faq-zeitstempel/
 */
final class MakeDfnTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'https://zeitstempel.dfn.de';

    #[Override]
    protected function getChain(): string
    {
        return dirname(__DIR__) . '/certs/dfn-chain.pem';
    }

    #[Override]
    protected function getCert(): string
    {
        return dirname(__DIR__) . '/certs/dfn.pem';
    }
}
