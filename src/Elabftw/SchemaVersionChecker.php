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
 * Use this to check for latest version or update the database schema
 */
final class SchemaVersionChecker
{
    /** @var int REQUIRED_SCHEMA the current version of the database structure */
    public const int REQUIRED_SCHEMA = 204;

    public function __construct(public int $currentSchema) {}

    /**
     * Check if the Db structure needs updating
     */
    public function checkSchema(): void
    {
        if ($this->currentSchema !== self::REQUIRED_SCHEMA) {
            throw new InvalidSchemaException();
        }
    }
}
