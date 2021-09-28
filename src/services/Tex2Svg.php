<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function dirname;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ProcessFailedException;
use function file_put_contents;
use function html_entity_decode;
use function is_dir;
use function mkdir;
use Mpdf\Mpdf;
use Mpdf\SizeConverter;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use Psr\Log\NullLogger;
use function str_replace;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;
use function tempnam;
use function unlink;

/**
 * Process HTML and transform tex into svg
 */
class Tex2Svg
{
    public function __construct(private Mpdf $mpdf, private string $source)
    {
    }

    public function getContent(): string
    {
        // decode html entities, otherwise it crashes
        // compare to https://github.com/mathjax/MathJax-demos-node/issues/16
        $contentDecode = html_entity_decode($this->source, ENT_HTML5, 'UTF-8');
        $html = $this->runNodeApp($contentDecode);

        // was there actually tex in the content?
        // if not we can skip the svg modifications and return the content
        // return the decoded content to avoid html entities issues in final pdf
        // see #2760
        if ($html === '') {
            return $contentDecode;
        }

        // based on https://github.com/mpdf/mpdf-examples/blob/master/MathJaxProcess.php
        $sizeConverter = new SizeConverter($this->mpdf->dpi, $this->mpdf->default_font_size, $this->mpdf, new NullLogger());

        // scale SVG size according to pdf + font settings
        // only select mathjax svg
        preg_match_all('/<mjx-container[^>]*><svg([^>]*)/', $html, $mathJaxSvg);
        foreach ($mathJaxSvg[1] as $svgAttributes) {
            preg_match('/width="(.*?)"/', $svgAttributes, $wr);
            preg_match('/height="(.*?)"/', $svgAttributes, $hr);

            if ($wr && $hr) {
                $w = $sizeConverter->convert($wr[1], 0, $this->mpdf->FontSize) * $this->mpdf->dpi / 25.4;
                $h = $sizeConverter->convert($hr[1], 0, $this->mpdf->FontSize) * $this->mpdf->dpi / 25.4;

                $html = str_replace('width="' . $wr[1] . '"', 'width="' . $w . '"', $html);
                $html = str_replace('height="' . $hr[1] . '"', 'height="' . $h . '"', $html);
            }
        }

        // add 'mathjax-svg' class to all mathjax SVGs
        $html = preg_replace('/(<mjx-container[^>]*><svg)/', '\1 class="mathjax-svg"', $html);

        // fill to white for all SVGs
        return str_replace('fill="currentColor"', 'fill="#000"', $html);
    }

    private function getTemporaryFilename(): string
    {
        // we use a custom tmp dir
        $tmpDir = dirname(__DIR__, 2) . '/cache/mathjax/';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            throw new FilesystemErrorException("Could not create the $tmpDir directory! Please check permissions on this folder.");
        }

        // temporary file to hold the content
        $filename = tempnam($tmpDir, '');
        if (!$filename) {
            throw new FilesystemErrorException("Could not create a temporary file in $tmpDir! Please check permissions on this folder.");
        }
        return $filename;
    }

    private function runNodeApp(string $content): string
    {
        $tmpFile = $this->getTemporaryFilename();

        file_put_contents($tmpFile, $content);

        // absolute path to tex2svg app
        $appDir = dirname(__DIR__, 2) . '/src/node';

        // convert tex to svg with mathjax nodejs script
        // returns nothing if there is no tex
        // use tex2svg.bundle.js script located in src/node
        // tex2svg.bundle.js is webpacked src/node/tex2svg.js
        $process = new Process(
            array(
                'node',
                $appDir . '/tex2svg.bundle.js',
                $tmpFile,
            )
        );
        $process->run();

        unlink($tmpFile);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException('PDF generation failed during Tex rendering.', 0, new SymfonyProcessFailedException($process));
        }
        return $process->getOutput();
    }
}
