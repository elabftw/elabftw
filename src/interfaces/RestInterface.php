<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Interfaces;

use Elabftw\Enums\Action;

/**
 * For models that are call by api v2
 */
interface RestInterface
{
    // TODO public function createOne(UserParamsCollection $params): int

    // TODO have only one read() and if id is set, readOne, else readAll
    public function readOne(): array;

    public function readAll(): array;

    public function postAction(Action $action, array $reqBody): int;

    public function patch(array $params): array;

    public function patchAction(Action $action): array;

    public function getViewPage(): string;

    public function destroy(): bool;
}
