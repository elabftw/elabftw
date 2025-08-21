<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Enums\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;

class UploadsMigratorTest extends \PHPUnit\Framework\TestCase
{
    public function testMigrate(): void
    {
        // create a non-persistent filesystem stored in memory
        $sourceFs = Storage::LOCAL->getStorage()->getFs();
        $targetFs = Storage::MEMORY->getStorage()->getFs();
        $UploadsMigrator = new UploadsMigrator(new ConsoleOutput(), $sourceFs, $targetFs);
        // number of uploads will be random each run
        $this->assertIsInt($UploadsMigrator->migrate());
    }
}
