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
 * RFC3161 timestamping with Digicert timestamping service
 * https://knowledge.digicert.com/generalinformation/INFO4231.html
 */
final class MakeDigicertTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'http://timestamp.digicert.com';

    #[Override]
    protected function getChain(): string
    {
        return '/etc/ssl/cert.pem';
    }

    #[Override]
    protected function getCert(): string
    {
        return dirname(__DIR__) . '/certs/digicert.pem';
    }
}
