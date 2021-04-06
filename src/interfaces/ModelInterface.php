<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Interfaces;

/**
 * Interface for models that can have CRUD operations
 */
interface ModelInterface
{
    public function create(CreateParamsInterface $params): int;

    public function update(UpdateParamsInterface $params): bool;

    public function destroy(): bool;
}
