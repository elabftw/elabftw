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
 * RFC3161 timestamping with Sectigo timestamping service
 * https://sectigo.com/resource-library/time-stamping-server
 */
final class MakeSectigoTimestamp extends AbstractMakeTrustedTimestamp
{
    protected const string TS_URL = 'http://timestamp.sectigo.com/';
}
