<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Storage;

use Aws\Credentials\Credentials;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Config;
use League\Flysystem\Filesystem;

class S3Test extends \PHPUnit\Framework\TestCase
{
    protected Credentials $credentials;

    protected StorageInterface $storage;

    protected function setUp(): void
    {
        $this->credentials = new Credentials('access-key', 'secret-key');
        $this->storage = new S3(Config::getConfig(), $this->credentials);

    }

    public function testGetFs(): void
    {
        $this->assertInstanceOf(Filesystem::class, $this->storage->getFs());
    }

    public function testGetBucketName(): void
    {
        $this->assertIsString($this->storage->getBucketName());
    }

    public function testGetPath(): void
    {
        $this->assertIsString($this->storage->getPath());
    }
}
