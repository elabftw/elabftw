<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class UploadsCleanerTest extends \PHPUnit\Framework\TestCase
{
    public function testCleanup(): void
    {
        // create a non-persistant filesystem stored in memory
        $fs = (new StorageFactory(StorageFactory::MEMORY))->getStorage()->getFs();
        // add a file to our filesystem so we can test removing it
        $fs->write('blah.txt', 'blih');
        // also add a thumbnail file that should not be removed
        $fs->write('blah_th.jpg', 'blih');

        $UploadsCleaner= new UploadsCleaner($fs);
        $this->assertEquals(1, $UploadsCleaner->cleanup());
    }
}
