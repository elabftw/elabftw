<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Services\TimestampUtils;

/**
 * Interface for trusted timestamp makers
 */
interface MakeTrustedTimestampInterface extends MakeTimestampInterface
{
    public function getTimestampUtils(): TimestampUtils;

    public function getTimestampParameters(): array;

    public function saveTimestamp(TimestampResponse $tsResponse, CreateUploadParamsInterface $create): int;
}
