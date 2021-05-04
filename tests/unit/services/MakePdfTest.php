<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Services\MakePdf;

//use League\Flysystem\Memory\MemoryAdapter;
//use League\Flysystem\Filesystem;

class MakePdfTest extends \PHPUnit\Framework\TestCase
{
    private MakePdf $MakePdf;

    protected function setUp(): void
    {
        $Entity = new Experiments(new Users(1, 1));
        $this->MakePdf = new MakePdf($Entity);
    }

    public function testOutput(): void
    {
        // TODO makepdf should have a filesystem class in DI
        //$this->MakePdf->output(true, true);
        //$this->assertFileExists($this->MakePdf->filePath);
    }
}
