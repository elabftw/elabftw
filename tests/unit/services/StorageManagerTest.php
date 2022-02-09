<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use League\Flysystem\Filesystem;

class StorageManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetStorage(): void
    {
        $mgr = new StorageManager(1);
        $this->assertInstanceOf(Filesystem::class, $mgr->getStorageFs());
        $mgr = new StorageManager(2);
        $this->assertInstanceOf(Filesystem::class, $mgr->getStorageFs());
        $mgr = new StorageManager(3);
        $this->assertInstanceOf(Filesystem::class, $mgr->getStorageFs());
        $mgr = new StorageManager(4);
        $this->assertInstanceOf(Filesystem::class, $mgr->getStorageFs());
    }
}
