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
use Elabftw\Elabftw\FsTools;
use Elabftw\Interfaces\MpdfProviderInterface;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Get an instance of mpdf
 */
class MpdfProvider implements MpdfProviderInterface
{
    public function __construct(private string $author, private string $format = 'A4', private bool $pdfa = false)
    {
    }

    public function getInstance(): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // create the pdf
        $mpdf = new Mpdf(array(
            'format' => $this->format,
            'tempDir' => FsTools::getCacheFolder('mpdf'),
            'mode' => 'utf-8',
            'fontDir' => array_merge($fontDirs, array(dirname(__DIR__, 2) . '/web/assets/fonts')),
            'fontdata' => $fontData + array(
                'lato' => array(
                    'R' => 'lato-medium-webfont.ttf',
                ),
            ),
            'default_font' => 'lato',
            // disallow getting external things
            'whitelistStreamWrappers' => array(''),
        ));

        // make sure we can read the pdf in a long time
        // will embed the font and make the pdf bigger
        $mpdf->PDFA = $this->pdfa;
        // force pdfa compliance (things like removing alpha channel of png images)
        if ($this->pdfa) {
            $mpdf->PDFAauto = true;
        }

        // make sure header and footer are not overlapping the body text
        $mpdf->setAutoTopMargin = 'stretch';
        $mpdf->setAutoBottomMargin = 'stretch';

        // set metadata
        $mpdf->SetAuthor($this->author);
        $mpdf->SetTitle('eLabFTW pdf');
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetCreator('www.elabftw.net');

        return $mpdf;
    }
}
