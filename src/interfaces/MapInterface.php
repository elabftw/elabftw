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
 * Interface for maps objects
 */
interface MapInterface
{
    /**
     * Save the object in sql
     */
    public function save(): bool;

    /**
     * Hydrate the object from a source (sql or post)
     *
     * @param array<string, mixed> $source
     */
    public function hydrate(array $source): void;
}
