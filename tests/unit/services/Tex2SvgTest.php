<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ProcessFailedException;
use function file_get_contents;
use function is_string;
use Mpdf\Mpdf;

class Tex2SvgTest extends \PHPUnit\Framework\TestCase
{
    private Mpdf $mpdf;

    protected function setUp(): void
    {
        $MpdfProvider = new MpdfProvider('Toto');
        $this->mpdf = $MpdfProvider->getInstance();
    }

    public function testNoMathJax(): void
    {
        $mathJaxHtml = '<html><head></head><body>No Tex here</body></html>';
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $this->assertEquals($mathJaxHtml, $Tex2Svg->getContent());
    }

    public function testMathJax(): void
    {
        $mathJaxHtml = $this->getFixture('mathjax.html');
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $mathJaxOut = $Tex2Svg->getContent();
        $mathJaxOutExpect = $this->getFixture('mathjax.out.html');
        $this->assertEquals($mathJaxOutExpect, $mathJaxOut);
    }

    public function testMathJaxFail(): void
    {
        $mathJaxHtml = $this->getFixture('mathjaxFail.html');
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $this->expectException(ProcessFailedException::class);
        $Tex2Svg->getContent();
    }

    private function getFixture(string $filename): string
    {
        $content = file_get_contents(dirname(__DIR__, 2) . '/_data/' . $filename);
        if (!is_string($content)) {
            throw new FilesystemErrorException('Error loading test file!');
        }
        return $content;
    }
}
