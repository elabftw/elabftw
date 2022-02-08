<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Aws\Credentials\Credentials;
use Elabftw\Models\Config;
use League\Flysystem\FilesystemAdapter;

class S3AdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAdapter(): void
    {
        $credentials = new Credentials('access-key', 'secret-key');
        $Adapter = new S3Adapter(Config::getConfig(), $credentials);
        $this->assertInstanceOf(FilesystemAdapter::class, $Adapter->getAdapter());
    }
}
