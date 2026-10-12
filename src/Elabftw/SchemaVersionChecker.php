<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\InvalidSchemaException;

/**
 * Check the legacy schema baseline and migration state.
 */
final class SchemaVersionChecker
{
    /**
     * Legacy numeric schema baseline. New migrations are tracked in schema_migrations.
     */
    public const int REQUIRED_SCHEMA = 228;

    public function __construct(public int $currentSchema, private readonly Migrations $Migrations)
    {
    }

    /**
     * Check if the Db structure needs updating.
     */
    public function checkSchema(): void
    {
        $pending = $this->Migrations->getPendingCount();
        if ($this->currentSchema !== self::REQUIRED_SCHEMA || $pending > 0) {
            throw new InvalidSchemaException($this->currentSchema, self::REQUIRED_SCHEMA, $pending);
        }
    }
}
