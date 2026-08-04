<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\Storage;
use League\Flysystem\Filesystem;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Mpdf\Mpdf;
use Psr\Log\LoggerInterface;

use function base64_decode;
use function preg_match;
use function str_replace;
use function substr_count;

class Tex2SvgTest extends \PHPUnit\Framework\TestCase
{
    private Mpdf $mpdf;

    private Filesystem $fixturesFs;

    private LoggerInterface $log;

    protected function setUp(): void
    {
        $MpdfProvider = new MpdfProvider('Toto');
        $this->mpdf = $MpdfProvider->getInstance();
        $this->fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $this->log  = (new Logger('elabftw'))->pushHandler(new NullHandler());
    }

    public function testNoMathJax(): void
    {
        $mathJaxHtml = '<html><head></head><body>No Tex here</body></html>';
        $Tex2Svg = new Tex2Svg($this->log, $this->mpdf, $mathJaxHtml);
        $this->assertEquals($mathJaxHtml, $Tex2Svg->getContent());
    }

    public function testMathJax(): void
    {
        $source = $this->fixturesFs->read('mathjax.html');
        $Tex2Svg = new Tex2Svg(
            $this->log,
            (new MpdfProvider('Toto', 'A4', true))->getInstance(),
            $source
        );

        $output = $Tex2Svg->getContent();

        $this->assertFalse($Tex2Svg->mathJaxFailed);
        $this->assertNotSame($source, $output);
        $this->assertSame(3, substr_count($output, 'class="mathjax-svg"'));
        $this->assertStringContainsString('data:image/png;base64,', $output);
        $this->assertStringNotContainsString('<mjx-break', $output);
    }

    public function testMathJaxNoPDFA(): void
    {
        $source = $this->fixturesFs->read('mathjax.html');

        $pdfa = new Tex2Svg(
            $this->log,
            (new MpdfProvider('Toto', 'A4', true))->getInstance(),
            $source
        );
        $regular = new Tex2Svg($this->log, $this->mpdf, $source);

        $pdfaOutput = $pdfa->getContent();
        $regularOutput = $regular->getContent();

        $this->assertFalse($pdfa->mathJaxFailed);
        $this->assertFalse($regular->mathJaxFailed);
        $this->assertStringContainsString('data:image/png;base64,', $pdfaOutput);
        $this->assertStringContainsString('data:image/png;base64,', $regularOutput);

        // The rasterized image differs because PDF/A uses an opaque background.
        $this->assertNotSame(
            $this->extractEmbeddedPng($pdfaOutput),
            $this->extractEmbeddedPng($regularOutput)
        );
    }

    public function testMathJaxFail(): void
    {
        $mathJaxHtml = $this->fixturesFs->read('mathjaxFail.html');
        $Tex2Svg = new Tex2Svg($this->log, $this->mpdf, $mathJaxHtml);
        $mathJaxOut = $Tex2Svg->getContent();
        $this->assertEquals($mathJaxHtml, $mathJaxOut);
    }

    public function testMhchemWithUmlaut(): void
    {
        $source = '<html><body>$$ \ce{A ->[\text{λ > 315 nm}] A^\text{*} ->[\text{λ > 280 nm}] \text{B (Verfärbung)}} $$</body></html>';

        $withUmlaut = new Tex2Svg($this->log, $this->mpdf, $source);
        $withUmlautOutput = $withUmlaut->getContent();

        // If the umlaut is dropped during rendering, this produces the same image.
        $withoutUmlaut = new Tex2Svg(
            $this->log,
            $this->mpdf,
            str_replace('ä', '', $source)
        );
        $withoutUmlautOutput = $withoutUmlaut->getContent();

        $this->assertFalse($withUmlaut->mathJaxFailed);
        $this->assertFalse($withoutUmlaut->mathJaxFailed);

        $withUmlautPng = $this->extractEmbeddedPng($withUmlautOutput);
        $withoutUmlautPng = $this->extractEmbeddedPng($withoutUmlautOutput);

        $this->assertNotSame(
            $withoutUmlautPng,
            $withUmlautPng,
            'Rendering ä must change the rasterized formula.'
        );
    }

    public function testAutoloadEquivalentExtensions(): void
    {
        $source = <<<'HTML'
            <html><body>
            $$
            \newcommand{\foo}{x}
            \cancel{\foo}
            + \braket{\phi|\psi}
            + \textcolor{red}{y}
            $$
            </body></html>
            HTML;

        $Tex2Svg = new Tex2Svg($this->log, $this->mpdf, $source);
        $output = $Tex2Svg->getContent();

        $this->assertFalse($Tex2Svg->mathJaxFailed);
        $this->assertStringContainsString('<mjx-container', $output);
    }

    private function extractEmbeddedPng(string $output): string
    {
        $result = preg_match(
            '/data:image\/png;base64,([^"]+)/',
            $output,
            $matches
        );

        $this->assertSame(1, $result);

        $png = base64_decode($matches[1], true);
        $this->assertNotFalse($png);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);

        return $png;
    }
}
