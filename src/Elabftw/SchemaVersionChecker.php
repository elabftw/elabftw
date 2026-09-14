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
use Symfony\Component\Console\Output\NullOutput;

use function dirname;

/**
 * Use this to check for latest version or update the database schema
 */
final class SchemaVersionChecker
{
    /** Final numbered schema, also used by the frozen installation snapshot. Do not increment. */
    public const int REQUIRED_SCHEMA = 224;

    public function __construct(public int $currentSchema, private ?Migrations $migrations = null) {}

    /**
     * Check if the Db structure needs updating
     */
    public function checkSchema(): void
    {
        if ($this->currentSchema !== self::REQUIRED_SCHEMA) {
            throw new InvalidSchemaException($this->currentSchema, self::REQUIRED_SCHEMA);
        }
        $Migrations = $this->migrations ?? new Migrations(FsTools::getFs(dirname(__DIR__) . '/sql'), new NullOutput());
        if (!$Migrations->isCurrent()) {
            throw new InvalidSchemaException($this->currentSchema, self::REQUIRED_SCHEMA);
        }
    }
}
