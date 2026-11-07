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
 * https://www.d-trust.net/en
 */
class MakeDtrustTimestamp extends AbstractMakeAuthenticatedTimestamp
{
    protected const string TS_URL = 'https://d-trust-time.secrypt-services.de/d-trust-tspresponder/timestamp';

    protected const string TS_HASH = 'sha512';
}
