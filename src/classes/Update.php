<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Models\Config;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Use this to check for latest version or update the database schema
 *
 * How to modify the structure:
 * 1. add a file in src/sql/ named 'schemaXX.sql' where XX is the current schema version + 1
 * 2. this file should use transactions (see other files for examples)
 * 3. increment the REQUIRED_SCHEMA number
 * 4. Run `bin/console db:update`
 * 5. reflect the changes in src/sql/structure.sql
 * 6. reflect the changes in tests/_data/phpunit.sql if needed
 */
class Update
{
    /** @var int REQUIRED_SCHEMA the current version of the database structure */
    private const REQUIRED_SCHEMA = 57;

    public Config $Config;

    private Db $Db;

    private Sql $Sql;

    public function __construct(Config $config, Sql $sql)
    {
        $this->Config = $config;
        $this->Db = Db::getConnection();
        $this->Sql = $sql;
    }

    /**
     * Get the current required schema
     */
    public function getRequiredSchema(): int
    {
        return self::REQUIRED_SCHEMA;
    }

    /**
     * Check if the Db structure needs updating
     */
    public function checkSchema(): void
    {
        $currentSchema = (int) $this->Config->configArr['schema'];

        if ($currentSchema !== self::REQUIRED_SCHEMA) {
            throw new InvalidSchemaException();
        }
    }

    /**
     * Update the database schema if needed
     */
    public function runUpdateScript(): void
    {
        $currentSchema = (int) $this->Config->configArr['schema'];

        // do nothing if we're up to date
        if ($currentSchema === self::REQUIRED_SCHEMA) {
            return;
        }

        // old style update functions have been removed, so add a block to prevent upgrade from very very old to newest directly
        if ($currentSchema < 37) {
            throw new ImproperActionException('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        if ($currentSchema < 41) {
            throw new ImproperActionException('Please update first to latest version from 2.0 branch before updating to 3.0 branch! See documentation.');
        }

        // new style with SQL files instead of functions
        while ($currentSchema < self::REQUIRED_SCHEMA) {
            $this->Sql->execFile('schema' . (string) (++$currentSchema) . '.sql');
        }

        // remove cached twig templates (for non docker users)
        $this->cleanTmp();
    }

    /**
     * Delete things in the tmp folder (cache/elab)
     */
    private function cleanTmp(): void
    {
        $dir = dirname(__DIR__, 2) . '/cache/elab';
        if (!is_dir($dir)) {
            return;
        }
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file->getPathName()) : unlink($file->getPathName());
        }
    }
}
