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
 * For models that are called by api v2
 */
interface RestInterface
{
    public function readOne(): array;

    public function readAll(): array;

    public function postAction(Action $action, array $reqBody): int;

    public function patch(Action $action, array $params): array;

    public function getPage(): string;

    public function destroy(): bool;
}
