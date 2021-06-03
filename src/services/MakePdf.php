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

use DateTime;
use function dirname;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ProcessFailedException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Traits\TwigTrait;
use function file_get_contents;
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
use setasign\Fpdi\FpdiException;
use function str_replace;
use function strtolower;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;
use function tempnam;
use function unlink;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMake
{
    use TwigTrait;

    public string $longName;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     * @param bool $temporary do we need to save it in cache folder or uploads folder
     */
    public function __construct(AbstractEntity $entity, $temporary = false)
    {
        parent::__construct($entity);

        $this->longName = $this->getLongName() . '.pdf';

        if ($temporary) {
            $this->filePath = $this->getTmpPath() . $this->getUniqueString();
        } else {
            $this->filePath = $this->getUploadsPath() . $this->longName;
            $dir = dirname($this->filePath);
            if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
                throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
            }
        }

        // suppress the "A non-numeric value encountered" error from mpdf
        // see https://github.com/baselbers/mpdf/commit
        // 5cbaff4303604247f698afc6b13a51987a58f5bc#commitcomment-23217652
        error_reporting(E_ERROR);
    }

    /**
     * Generate pdf and output it to a file
     */
    public function outputToFile(): void
    {
        $this->generate()->Output($this->filePath, 'F');
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     */
    public function getContent(): string
    {
        $Request = Request::createFromGlobals();

        if ($this->Entity->entityData['tags']) {
            $tags = '<strong>Tags:</strong> <em>' .
                str_replace('|', ' ', $this->Entity->entityData['tags']) . '</em> <br />';
        }

        $date = new DateTime($this->Entity->entityData['date'] ?? Filter::kdate());

        $locked = $this->Entity->entityData['locked'];
        $lockDate = '';
        $lockerName = '';

        if ($locked) {
            // get info about the locker
            $Locker = new Users((int) $this->Entity->entityData['lockedby']);
            $lockerName = $Locker->userData['fullname'];

            // separate the date and time
            $ldate = explode(' ', $this->Entity->entityData['lockedwhen']);
            $lockDate = $ldate[0] . ' at ' . $ldate[1];
        }

        $renderArr = array(
            'body' => $this->getBody(),
            'commentsArr' => $this->Entity->Comments->read(new ContentParams()),
            'css' => $this->getCss(),
            'date' => $date->format('Y-m-d'),
            'elabid' => $this->Entity->entityData['elabid'],
            'fullname' => $this->Entity->entityData['fullname'],
            'includeFiles' => $this->Entity->Users->userData['inc_files_pdf'],
            'linksArr' => $this->Entity->Links->read(new ContentParams()),
            'locked' => $locked,
            'lockDate' => $lockDate,
            'lockerName' => $lockerName,
            'pdfSig' => $Request->cookies->get('pdf_sig'),
            'stepsArr' => $this->Entity->Steps->read(new ContentParams()),
            'tags' => $this->Entity->entityData['tags'],
            'title' => $this->Entity->entityData['title'],
            'uploadsArr' => $this->Entity->Uploads->readAll(),
            'uploadsFolder' => dirname(__DIR__, 2) . '/uploads/',
            'url' => $this->getUrl(),
            'linkBaseUrl' => Tools::getUrl($Request) . '/database.php',
            'useCjk' => $this->Entity->Users->userData['cjk_fonts'],
        );

        return $this->getTwig(Config::getConfig())->render('pdf.html', $renderArr);
    }

    /**
     * Generate pdf and return it as string
     */
    public function getPdf(): string
    {
        return $this->generate()->Output('', 'S');
    }

    /**
     * Replace weird characters by underscores
     */
    public function getFileName(): string
    {
        $title = Filter::forFilesystem($this->Entity->entityData['title']);
        return $this->Entity->entityData['date'] . ' - ' . $title . '.pdf';
    }

    /**
     * Initialize Mpdf
     */
    public function initializeMpdf(bool $multiEntity = false): Mpdf
    {
        $format = $this->Entity->Users->userData['pdf_format'];

        // we use a custom tmp dir, not the same as Twig because its content gets deleted after pdf is generated
        $tmpDir = dirname(__DIR__, 2) . '/cache/mpdf/';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            throw new FilesystemErrorException("Could not create the $tmpDir directory! Please check permissions on this folder.");
        }

        // create the pdf
        $mpdf = new Mpdf(array(
            'format' => $format,
            'tempDir' => $tmpDir,
            'mode' => 'utf-8',
        ));

        // make sure header and footer are not overlapping the body text
        $mpdf->setAutoTopMargin = 'stretch';
        $mpdf->setAutoBottomMargin = 'stretch';

        // set metadata
        $mpdf->SetAuthor($this->Entity->Users->userData['fullname']);
        $mpdf->SetTitle('eLabFTW pdf');
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetCreator('www.elabftw.net');

        if (!$multiEntity) {
            $mpdf->SetAuthor($this->Entity->entityData['fullname']);
            $mpdf->SetTitle($this->Entity->entityData['title']);
            $mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags'] ?? ''));
        }

        return $mpdf;
    }

    /**
     * Convert Tex to SVG with Mathjax
     */
    public function tex2svg(Mpdf $mpdf, string $content): string
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

        // decode html entities, otherwise it crashes
        // compare to https://github.com/mathjax/MathJax-demos-node/issues/16
        $contentDecode = html_entity_decode($content, ENT_HTML5, 'UTF-8');
        file_put_contents($filename, $contentDecode);

        // apsolute path to tex2svg app
        $appDir = dirname(__DIR__, 2) . '/src/node';

        // convert tex to svg with mathjax nodejs script
        // returns nothing if there is no tex
        // use tex2svg.bundle.js script located in src/node
        // tex2svg.bundle.js is webpacked src/node/tex2svg.js
        $process = new Process(
            array(
                'node',
                $appDir . '/tex2svg.bundle.js',
                $filename,
            )
        );
        $process->run();

        if (!$process->isSuccessful()) {
            unlink($filename);
            throw new ProcessFailedException('PDF generation failed during Tex rendering.', 0, new SymfonyProcessFailedException($process));
        }

        $html = $process->getOutput();
        unlink($filename);

        // was there actually tex in the content?
        // if not we can skip the svg modifications and return the original content
        if ($html === '') {
            return $content;
        }

        // based on https://github.com/mpdf/mpdf-examples/blob/master/MathJaxProcess.php
        // ˅˅˅˅˅˅˅˅˅˅
        $sizeConverter = new SizeConverter($mpdf->dpi, $mpdf->default_font_size, $mpdf, new NullLogger());

        // scale SVG size according to pdf + font settings
        // only select mathjax svg
        preg_match_all('/<mjx-container[^>]*><svg([^>]*)/', $html, $mathJaxSvg);
        foreach ($mathJaxSvg[1] as $svgAttributes) {
            preg_match('/width="(.*?)"/', $svgAttributes, $wr);
            preg_match('/height="(.*?)"/', $svgAttributes, $hr);

            if ($wr && $hr) {
                $w = $sizeConverter->convert($wr[1], 0, $mpdf->FontSize) * $mpdf->dpi / 25.4;
                $h = $sizeConverter->convert($hr[1], 0, $mpdf->FontSize) * $mpdf->dpi / 25.4;

                $html = str_replace('width="' . $wr[1] . '"', 'width="' . $w . '"', $html);
                $html = str_replace('height="' . $hr[1] . '"', 'height="' . $h . '"', $html);
            }
        }

        // add 'mathjax-svg' class to all mathjax SVGs
        $html = preg_replace('/(<mjx-container[^>]*><svg)/', '\1 class="mathjax-svg"', $html);

        // fill to white for all SVGs
        return str_replace('fill="currentColor"', 'fill="#000"', $html);

        // ˄˄˄˄˄˄˄˄˄˄
        // end
    }

    /**
     * Append PDFs attached to an entity
     *
     * @param Mpdf $mpdf
     */
    public function appendPDFs(Mpdf $mpdf): Mpdf
    {
        $listOfPdfs = $this->getListOfPdfs();

        if (empty($listOfPdfs)) {
            return $mpdf;
        }

        foreach ($listOfPdfs as $pdf) {
            // There will be cases where the merging will fail
            // due to incompatibilities of Mpdf (actually fpdi) with the pdfs
            // See https://manuals.setasign.com/fpdi-manual/v2/limitations/
            // These cases will be caught and ignored
            try {
                $numberOfPages = $mpdf->setSourceFile($pdf[0]);

                for ($i = 1; $i <= $numberOfPages; $i++) {
                    // Import the ith page of the source PDF file
                    $page = $mpdf->importPage($i);

                    // getTemplateSize() is not documented in the MPDF manual
                    // @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
                    $pageDim = $mpdf->getTemplateSize($page);

                    if (is_array($pageDim)) { // satisfy phpstan
                        // add a new (blank) page with the dimensions of the imported page
                        $mpdf->AddPageByArray(array(
                            'orientation' => $pageDim['orientation'],
                            'sheet-size' => array($pageDim['width'], $pageDim['height']),
                        ));
                    }

                    // empty the header and footer
                    // cannot be an empty string
                    $mpdf->SetHTMLHeader(' ', '', true);
                    $mpdf->SetHTMLFooter(' ', '');

                    // add the content of the imported page
                    $mpdf->useTemplate($page);
                }
            } catch (FpdiException) {
                continue;
            }
        }

        return $mpdf;
    }

    /**
     * Get a list of all PDFs that are attached to an entity
     *
     * @return array Empty or array of arrays with information for PDFs array('path/to/file', 'real.name')
     */
    private function getListOfPdfs(): array
    {
        $uploadsArr = $this->Entity->Uploads->readAll();
        $listOfPdfs = array();

        if (empty($uploadsArr)) {
            return $listOfPdfs;
        }

        foreach ($uploadsArr as $upload) {
            $filePath = dirname(__DIR__, 2) . '/uploads/' . $upload['long_name'];
            if (file_exists($filePath) && strtolower(Tools::getExt($upload['real_name'])) === 'pdf') {
                $listOfPdfs[] = array($filePath, $upload['real_name']);
            }
        }

        return $listOfPdfs;
    }

    /**
     * Build the pdf
     */
    private function generate(): Mpdf
    {
        $mpdf = $this->initializeMpdf();

        // write content
        $mpdf->WriteHTML($this->tex2svg($mpdf, $this->getContent()));

        if ($this->Entity->Users->userData['append_pdfs']) {
            $mpdf = $this->appendPDFs($mpdf);
        }

        if ($this->Entity->Users->userData['pdfa']) {
            // make sure we can read the pdf in a long time
            // will embed the font and make the pdf bigger
            $mpdf->PDFA = true;
        }

        return $mpdf;
    }

    /**
     * Get the contents of assets/pdf.min.css
     */
    private function getCss(): string
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/web/assets/pdf.min.css');
        if ($css === false) {
            throw new FilesystemErrorException('Cannot read the minified css file!');
        }
        return $css;
    }

    private function getBody(): string
    {
        $body = $this->Entity->entityData['body'];

        // convert to html if we have markdown
        if ($this->Entity->Users->userData['use_markdown']) {
            $body = Tools::md2html($body);
        }
        // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
        return str_replace('src="app/download.php?f=', 'src="' . dirname(__DIR__, 2) . '/uploads/', $body);
    }
}
