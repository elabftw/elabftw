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
use function file_put_contents;
use function html_entity_decode;
use Imagick;
use function is_dir;
use function mkdir;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
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
    // mm per inch
    private const MM_PER_INCH = 25.4;

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

        // scale SVG size according to pdf + font settings,
        // convert nested SVGs to PNGs here as mPDF cannot do it, v8.0.13
        // get all MathJax SVGs, use named subpatterns ('svg', 'attributes'), delimiter is '#'
        preg_match_all(
            '#<mjx-container[^>]*>(?P<svg><svg(?P<attributes>[^>]*)>[\s\S]*?)</mjx-container>#',
            $html,
            $mathJaxSvgs,
            PREG_SET_ORDER
        );
        foreach ($mathJaxSvgs as $mathJaxSvg) {
            // get the SVG dimensions
            preg_match('/width="(?P<value>.*?)"/', $mathJaxSvg['attributes'], $width);
            preg_match('/height="(?P<value>.*?)"/', $mathJaxSvg['attributes'], $height);
            $w = $width['value'];
            $h = $height['value'];

            if ($width && $height) {
                // MathJax dimensions are in 'ex', convert() returns 'mm' -> final is pixel
                // scale SVG size according to pdf + font settings
                $scaleFactor = $this->mpdf->dpi / self::MM_PER_INCH;
                $w = $sizeConverter->convert($w, 0, $this->mpdf->FontSize) * $scaleFactor;
                $h = $sizeConverter->convert($h, 0, $this->mpdf->FontSize) * $scaleFactor;
            }

            // Is this a nested SVG?
            // fix https://github.com/elabftw/elabftw/pull/2509#issuecomment-788645472
            // mpdf cannot handle nested SVGs, so we convert them upfront to PNG images
            if (substr_count($mathJaxSvg['svg'], '</svg>') > 1) {
                $image = new Imagick();
                $image->setResolution(300, 300);
                $image->setBackgroundColor('#0000'); // #rgba, a=0: fully transparent
                $image->readImageBlob('<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $mathJaxSvg['svg']);
                $image->setImageFormat('png');
                // remove all profiles and comments including date:create, date:modify which conflicts with unit testing
                $image->stripImage();
                $img = sprintf(
                    '<img src="data:image/png;base64,%s" width="%d" height="%d" class="mathjax-svg">',
                    base64_encode($image->getImageBlob()),
                    $w,
                    $h
                );
                $image->clear();
                // replace <mjx-container>...</mjx-container> with plain <img>
                $html = str_replace($mathJaxSvg[0], $img, $html);
            } else {
                // resize remaining MathJax SVGs
                $html = str_replace('width="' . $width['value'] . '"', 'width="' . $w . '"', $html);
                $html = str_replace('height="' . $height['value'] . '"', 'height="' . $h . '"', $html);
            }
        }

        // add 'mathjax-svg' class to all mathjax SVGs
        $html = preg_replace('/(<mjx-container[^>]*><svg)/', '\1 class="mathjax-svg"', $html);

        // fill to black for all SVGs
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
            $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
            // don't spam the log file with all the webpacked bundle gibberish
            $process->clearErrorOutput();
            // Log a generic error
            $log->warning('PDF generation failed during Tex rendering.', array('Error', new SymfonyProcessFailedException($process)));
            // Throwing an error here will block PDF generation. This should be avoided.
            // https://github.com/elabftw/elabftw/issues/3076#issuecomment-997197700
            // Returning an empty string will generate a pdf without type setting tex math expressions
            // The raw tex will be retained so no information is lost
            return '';
        }
        return $process->getOutput();
    }
}
