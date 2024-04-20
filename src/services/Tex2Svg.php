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

use Elabftw\Elabftw\FsTools;
use Imagick;
use Mpdf\Mpdf;
use Mpdf\SizeConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;

use function dirname;
use function file_put_contents;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function unlink;

/**
 * Process HTML and transform tex into svg
 *
 * Note: this shouldn't be needed with psalm running inside the container!
 * @psalm-suppress UndefinedClass
 */
class Tex2Svg
{
    // mm per inch
    private const INCH_TO_MM_CONVERSION_FACTOR = 25.4;

    public bool $mathJaxFailed = false;

    private string $contentWithMathJaxSVG = '';

    public function __construct(private LoggerInterface $log, private Mpdf $mpdf, private string $source) {}

    public function getContent(): string
    {
        $this->contentWithMathJaxSVG = $this->runNodeApp($this->source);

        // was there actually tex in the content?
        // if not we can skip the svg modifications and return the content
        // return the decoded content to avoid html entities issues in final pdf
        // see #2760
        if ($this->contentWithMathJaxSVG === '') {
            return $this->source;
        }

        $this->scaleSVGs();

        // add 'mathjax-svg' class to all mathjax SVGs
        $this->contentWithMathJaxSVG = (string) preg_replace('/(<mjx-container[^>]*><svg)/', '\1 class="mathjax-svg"', $this->contentWithMathJaxSVG);

        // fill to black for all SVGs
        return str_replace('fill="currentColor"', 'fill="#000"', $this->contentWithMathJaxSVG);
    }

    private function runNodeApp(string $content): string
    {
        $tmpFile = FsTools::getCacheFile();

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
            // don't spam the log file with all the webpacked bundle gibberish
            $process->clearErrorOutput();
            // Log a generic error
            $this->log->warning('PDF generation failed during Tex rendering.', array('Error', new SymfonyProcessFailedException($process)));

            $this->mathJaxFailed = true;
            // Throwing an error here will block PDF generation. This should be avoided.
            // https://github.com/elabftw/elabftw/issues/3076#issuecomment-997197700
            // Returning an empty string will generate a pdf without type setting tex math expressions
            // The raw tex will be retained so no information is lost
            return '';
        }
        return $process->getOutput();
    }

    // scale SVG size according to pdf + font settings,
    // convert nested SVGs to PNGs here as mPDF cannot do it, v8.0.13
    private function scaleSVGs(): void
    {
        // based on https://github.com/mpdf/mpdf-examples/blob/master/MathJaxProcess.php
        $sizeConverter = new SizeConverter($this->mpdf->dpi, $this->mpdf->default_font_size, $this->mpdf, new NullLogger());

        // get all MathJax SVGs, use named subpatterns ('svg', 'attributes'), delimiter is '#'
        preg_match_all(
            '#<mjx-container[^>]*>(?P<svg><svg(?P<attributes>[^>]*)>[\s\S]*?)</mjx-container>#',
            $this->contentWithMathJaxSVG,
            $mathJaxSvgs,
            PREG_SET_ORDER
        );
        foreach ($mathJaxSvgs as $mathJaxSvg) {
            // get the SVG dimensions
            preg_match('/width="(?P<mathJax>.*?)"/', $mathJaxSvg['attributes'], $width);
            preg_match('/height="(?P<mathJax>.*?)"/', $mathJaxSvg['attributes'], $height);

            if ($width && $height) {
                // MathJax dimensions are in 'ex', convert() returns 'mm' -> final is pixel
                // scale SVG size according to pdf + font settings
                $scaleFactor = $this->mpdf->dpi / self::INCH_TO_MM_CONVERSION_FACTOR;
                $width['mpdf'] = $sizeConverter->convert($width['mathJax'], 0, $this->mpdf->FontSize) * $scaleFactor;
                $height['mpdf'] = $sizeConverter->convert($height['mathJax'], 0, $this->mpdf->FontSize) * $scaleFactor;

                // Is this a nested SVG?
                // fix https://github.com/elabftw/elabftw/pull/2509#issuecomment-788645472
                // mpdf cannot handle nested SVGs, so we convert them upfront to PNG images
                if (substr_count($mathJaxSvg['svg'], '</svg>') > 1) {
                    $this->nestedSvgToPng($mathJaxSvg[0], $mathJaxSvg['svg'], $width['mpdf'], $height['mpdf']);
                } else {
                    // resize remaining MathJax SVGs
                    $this->contentWithMathJaxSVG = str_replace(
                        'width="' . $width['mathJax'] . '"',
                        'width="' . $width['mpdf'] . '"',
                        $this->contentWithMathJaxSVG
                    );
                    $this->contentWithMathJaxSVG = str_replace(
                        'height="' . $height['mathJax'] . '"',
                        'height="' . $height['mpdf'] . '"',
                        $this->contentWithMathJaxSVG
                    );
                }
            }
        }
    }

    private function nestedSvgToPng(string $mjxContainer, string $svg, float $width, float $height): void
    {
        $image = new Imagick();
        $image->setRegistry('temporary-path', FsTools::getCacheFolder('elab'));
        // resolution could be lower to reduce file size
        $image->setResolution(300, 300);
        // do not use alpha channel if PDFA
        $image->setBackgroundColor('#FFF' . ($this->mpdf->PDFA === true ? '' : '0')); // #rgba, a=0: fully transparent
        $image->readImageBlob('<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $svg);
        $image->setImageFormat('png');
        // remove all profiles and comments including date:create, date:modify which conflicts with unit testing
        $image->stripImage();
        $img = sprintf(
            '<img src="data:image/png;base64,%s" width="%d" height="%d" class="mathjax-svg">',
            base64_encode($image->getImageBlob()),
            $width,
            $height
        );
        $image->clear();
        // replace <mjx-container>...</mjx-container> with plain <img>
        $this->contentWithMathJaxSVG = str_replace($mjxContainer, $img, $this->contentWithMathJaxSVG);
    }
}
