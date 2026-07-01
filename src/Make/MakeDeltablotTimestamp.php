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

/**
 * RFC3161 timestamping with Evidency service through Deltablot proxy
 */
class MakeDeltablotTimestamp extends AbstractMakeAuthenticatedTimestamp
{
    protected const string TS_URL = 'https://tsa-proxy.deltablot.app/timestamp';

    protected const string TS_HASH = 'sha512';
}
