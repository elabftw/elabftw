<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Interfaces;

use Elabftw\Enums\State;

/**
 * Interface for timestamp makers
 */
interface MakeTimestampInterface
{
    public function getTimestampParameters(): array;

    public function getFileName(): string;

    public function saveTimestamp(string $dataPath, TimestampResponseInterface $tsResponse, State $state): int;

    public function generateData(): string;
}
