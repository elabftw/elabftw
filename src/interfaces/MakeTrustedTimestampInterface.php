<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Interfaces;

/**
 * Interface for trusted timestamp makers
 */
interface MakeTrustedTimestampInterface extends MakeTimestampInterface
{
    public function getTimestampParameters(): array;

    public function saveTimestamp(TimestampResponseInterface $tsResponse, CreateUploadParamsInterface $create): int;
}
