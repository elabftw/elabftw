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
 * Sandbox env which is used in DEV_MODE
 * https://docs.evidency.io/reference/v3projecttimestamp
 */
final class MakeEvidencyTimestampDev extends MakeEvidencyTimestamp
{
    protected const string TS_URL = 'https://api-sandbox.evidency.io/v3/projects/%s/timestamp';
}
