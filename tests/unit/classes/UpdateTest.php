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
        $Update = new Update(Update::REQUIRED_SCHEMA - 1, $this->Sql);
        $this->expectException(InvalidSchemaException::class);
        $Update->checkSchema();
    }

    public function testRunUpdateScript(): void
    {
        // create a fake schema file
        $this->Fs->write(sprintf('schema%d.sql', Update::REQUIRED_SCHEMA), 'SELECT 1');
        $Update = new Update(Update::REQUIRED_SCHEMA - 1, $this->Sql);
        $this->assertIsArray($Update->runUpdateScript());
    }

    public function testOldAfInstance(): void
    {
        $Update = new Update(36, $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }

    public function testVersion2(): void
    {
        $Update = new Update(40, $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }
}
