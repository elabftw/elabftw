<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use function file_get_contents;

class MathJaxTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users);
        $this->MakePdf = new MakePdf($this->Entity, true);
    }

    public function testNoMathJax()
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = '<html><head></head><body>No Tex here</body></html>';
        $mathJaxOut = $this->MakePdf->tex2svg($tmpdf, $mathJaxHtml);

        $this->assertEquals($mathJaxHtml, $mathJaxOutput);
    }

    public function testMathJax()
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjax.html');
        $mathJaxOut = $this->MakePdf->tex2svg($tmpdf, $mathJaxHtml);
        $mathJaxOutExpect = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjax.out.html');

        $this->assertEquals($mathJaxOutExpect, $mathJaxOutput);
    }

    public function testMathJaxFail()
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjaxFail.html');
        $this->expectException(FilesystemErrorException::class);
        $mathJaxOut = $this->MakePdf->tex2svg($tmpdf, $mathJaxHtml);
    }
}
