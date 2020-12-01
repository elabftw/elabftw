<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

class UploadsCleanerTest extends \PHPUnit\Framework\TestCase
{
    public function testCleanup()
    {
        // create a non-persistant filesystem stored in memory
        $filesystem = new Filesystem(new MemoryAdapter());
        // add a file to our filesystem so we can test removing it
        $filesystem->write('blah.txt', 'blih');
        // also add a thumbnail file that should not be removed
        $filesystem->write('blah_th.jpg', 'blih');

        $UploadsCleaner= new UploadsCleaner($filesystem);
        $this->assertEquals(1, $UploadsCleaner->cleanup());
    }
}
