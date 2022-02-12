<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class StorageFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetStorage(): void
    {
        $factory = new StorageFactory(1);
        $this->assertInstanceOf(LocalStorage::class, $factory->getStorage());
        $factory = new StorageFactory(2);
        $this->assertInstanceOf(S3Storage::class, $factory->getStorage());
        $factory = new StorageFactory(3);
        $this->assertInstanceOf(MemoryStorage::class, $factory->getStorage());
        $factory = new StorageFactory(4);
        $this->assertInstanceOf(CacheStorage::class, $factory->getStorage());
        $factory = new StorageFactory(5);
        $this->assertInstanceOf(FixturesStorage::class, $factory->getStorage());
        $factory = new StorageFactory(42);
        $this->assertInstanceOf(MemoryStorage::class, $factory->getStorage());
    }
}
