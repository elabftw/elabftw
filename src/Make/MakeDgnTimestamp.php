<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

/**
 * RFC3161 timestamping with DGN service
 * https://www.dgn.de/dgn-zeitstempeldienst/
 */
final class MakeDgnTimestamp extends AbstractMakeAuthenticatedTimestamp
{
    protected const string TS_URL = 'https://zeitstempel.dgn.de/tss';

    protected const string TS_HASH = 'sha512';
}
