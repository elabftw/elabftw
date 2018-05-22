<?php
/**
 * \Elabftw\Elabftw\CrudInterface
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * Interface for things like Steps, Links, Comments
 */
interface CrudInterface
{
    /**
     * Read all the things
     *
     * @return array
     */
    public function readAll(): array;

    /**
     * Destroy with id
     *
     * @param int $id Id of item to destroy
     *
     * @return bool
     */
    public function destroy(int $id): bool;

    /**
     * Detroy all the things
     *
     * @return bool
     */
    public function destroyAll(): bool;
}
