<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class UpdateTest extends \PHPUnit\Framework\TestCase
{
    private Fs $Fs;

    private Sql $Sql;

    public function setUp(): void
    {
        $this->Fs = new Fs(new InMemoryFilesystemAdapter());
        $this->Sql = new Sql($this->Fs);
    }

    public function testCheckSchema(): void
    {
        $checker = new SchemaVersionChecker(SchemaVersionChecker::REQUIRED_SCHEMA - 1);
        $this->expectException(InvalidSchemaException::class);
        $checker->checkSchema();
    }

    public function testRunUpdateScript(): void
    {
        // create a fake schema file
        $this->Fs->write(sprintf('schema%d.sql', SchemaVersionChecker::REQUIRED_SCHEMA), 'SELECT 1');
        $checker = new SchemaVersionChecker(SchemaVersionChecker::REQUIRED_SCHEMA - 1);
        $Update = new Update($checker, $this->Sql);
        $this->assertIsArray($Update->runUpdateScript());
        $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $checker->currentSchema);
    }

    public function testOldAfInstance(): void
    {
        $Update = new Update(new SchemaVersionChecker(36), $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }

    public function testVersion2(): void
    {
        $Update = new Update(new SchemaVersionChecker(40), $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }
}
