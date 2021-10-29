<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @author David Müller
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

/**
 * Same as parent with just the TS_URL different
 */
class MakeUniversignTimestampDev extends MakeUniversignTimestamp
{
    protected const TS_URL = 'https://sign.test.cryptolog.com/tsa';
}
