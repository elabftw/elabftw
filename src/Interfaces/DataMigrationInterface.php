<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

/**
 * Data that a migration moves with PHP, because SQL cannot do it (compression for instance)
 *
 * up() runs before the sql file of the migration and down() runs after its down file:
 * the old and the new structures both exist then.
 * Both must be safe to run again after a failure.
 */
interface DataMigrationInterface
{
    public function up(): void;

    public function down(): void;
}
