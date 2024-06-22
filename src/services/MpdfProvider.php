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
use Elabftw\Interfaces\MpdfProviderInterface;
// use Mpdf\Config\ConfigVariables;
// use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

use function dirname;

/**
 * Get an instance of mpdf
 */
class MpdfProvider implements MpdfProviderInterface
{
    public function __construct(private string $author, private string $format = 'A4', private bool $pdfa = false) {}

    public function isPdfa(): bool
    {
        return $this->pdfa;
    }

    public function getInstance(): Mpdf
    {
        // $fontVariables = (new FontVariables())->getDefaults();
        // $fontData = $fontVariables['fontdata'];
        // create the pdf
        $mpdf = new Mpdf(array(
            'format' => $this->format,
            'tempDir' => FsTools::getCacheFolder('mpdf'),
            'mode' => 'utf-8',
            'fontDir' => array_merge(
                //(new ConfigVariables())->getDefaults()['fontDir'],
                array(dirname(__DIR__, 2) . '/src/font/Noto'),
            ),
            'fontdata' => array_merge(
                array(
                    'notosans' => array(
                        'R' => 'NotoSans-Regular.ttf',
                        'I' => 'NotoSans-Italic.ttf',
                        'B' => 'NotoSans-Bold.ttf',
                        'BI' => 'NotoSans-BoldItalic.ttf',
                        //'useOTL' => 0xFF,
                    ),
                    'notoemoji' => array(
                        'R' => 'NotoEmoji-Regular.ttf',
                        'B' => 'NotoEmoji-Bold.ttf',
                    ),
                    // Japanese
                    'notosansjp' => array(
                        'R' => 'NotoSansJP-Regular.ttf',
                        'B' => 'NotoSansJP-Bold.ttf',
                    ),
                    // Korean
                    'notosanskr' => array(
                        'R' => 'NotoSansKR-Regular.ttf',
                        'B' => 'NotoSansKR-Bold.ttf',
                    ),
                    'notomath' => array(
                        'R' => 'NotoSansMath-Regular.ttf',
                    ),
                    'notosansmono' => array(
                        'R' => 'NotoSansMono-Regular.ttf',
                        'B' => 'NotoSansMono-Bold.ttf',
                    ),
                    // Simplified Chinese
                    'notosanssc' => array(
                        'R' => 'NotoSansSC-Regular.ttf',
                        'B' => 'NotoSansSC-Bold.ttf',
                    ),
                    'notosanssymbols' => array(
                        'R' => 'NotoSansSymbols-Regular.ttf',
                        'B' => 'NotoSansSumbols-Bold.ttf',
                    ),
                    'notosanssymbols2' => array(
                        'R' => 'NotoSansSymbols2-Regular.ttf',
                    ),
                    // Traditional Chinese
                    'notosanstc' => array(
                        'R' => 'NotoSansTC-Regular.ttf',
                        'B' => 'NotoSansTC-Bold.ttf',
                    ),
                ),
                //$fontData,
            ),
            'default_font' => 'notosans',
            'backupSubsFont' => array(
                'notosans',
                'notosanssymbols',
                'notosanssymbols2',
                'notomath',
                'notoemoji',
                'notosansjp',
                'notosanskr',
                'notosanssc',
                'notosanstc'),
            'fonttrans' => array('noto' => 'notosans'), //array_merge(, $fontVariables['fonttrans']),
            'sans_fonts' => array('notosans'), //array_merge(, $fontVariables['sans_fonts']),
            'mono_fonts' => array('notosansmono'), //array_merge(, $fontVariables['mono_fonts']),
            'useSubstitutions' => true,
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
