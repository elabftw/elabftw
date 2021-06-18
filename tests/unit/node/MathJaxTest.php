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
use Elabftw\Exceptions\ProcessFailedException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use function file_get_contents;

class MathJaxTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Entity;

    private MakePdf $MakePdf;

    protected function setUp(): void
    {
        $this->Entity = new Experiments(new Users(1, 1));
        $this->MakePdf = new MakePdf($this->Entity, true);
    }

    public function testNoMathJax(): void
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = '<html><head></head><body>No Tex here</body></html>';
        $mathJaxOut = $this->MakePdf->tex2svg($mpdf, $mathJaxHtml);

        $this->assertEquals($mathJaxHtml, $mathJaxOut);
    }

    /** TODO: disabled until we can upgrade to 3.1.5, when font size issue is fixed
    public function testMathJax(): void
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjax.html');
        if (!is_string($mathJaxHtml)) {
            throw new FilesystemErrorException('Error loading test file!');
        }
        $mathJaxOut = $this->MakePdf->tex2svg($mpdf, $mathJaxHtml);
        $mathJaxOutExpect = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjax.out.html');
        if (!is_string($mathJaxOutExpect)) {
            throw new FilesystemErrorException('Error loading test file!');
        }
        $this->assertEquals($mathJaxOutExpect, $mathJaxOut);
    }
     */
    public function testMathJaxFail(): void
    {
        $mpdf = $this->MakePdf->initializeMpdf();
        $mathJaxHtml = file_get_contents(dirname(__DIR__, 2) . '/_data/mathjaxFail.html');
        if (!is_string($mathJaxHtml)) {
            throw new FilesystemErrorException('Error loading test file!');
        }
        $this->expectException(ProcessFailedException::class);
        $mathJaxOut = $this->MakePdf->tex2svg($mpdf, $mathJaxHtml);
    }
}
