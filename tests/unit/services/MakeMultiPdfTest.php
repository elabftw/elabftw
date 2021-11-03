<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakeMultiPdfTest extends \PHPUnit\Framework\TestCase
{
    private MakeMultiPdf $MakePdf;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $Entity = new Experiments(new Users(1, 1));
        $MpdfProvider = new MpdfProvider('Toto');
        $this->MakePdf = new MakeMultiPdf($MpdfProvider, $Entity, $idArr);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('multientries.elabftw.pdf', $this->MakePdf->getFileName());
    }
}
