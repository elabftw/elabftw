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

/**
 * RFC3161 timestamping with Universign service
 * https://www.universign.com/en/
 */
class MakeUniversignTimestamp extends AbstractMakeAuthenticatedTimestamp
{
    protected const string TS_URL = 'https://ws.universign.eu/tsa';
}
