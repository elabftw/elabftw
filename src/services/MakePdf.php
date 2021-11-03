<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTime;
use function dirname;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Traits\PdfTrait;
use Elabftw\Traits\TwigTrait;
use Elabftw\Traits\UploadTrait;
use function is_dir;
use function mkdir;
use Mpdf\Mpdf;
use function preg_replace;
use setasign\Fpdi\FpdiException;
use function str_replace;
use function strtolower;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMake implements FileMakerInterface
{
    use TwigTrait;
    use PdfTrait;
    use UploadTrait;

    public string $longName;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     * @param bool $temporary do we need to save it in cache folder or uploads folder
     */
    public function __construct(MpdfProviderInterface $mpdfProvider, AbstractEntity $entity, $temporary = false)
    {
        parent::__construct($entity);

        $this->longName = $this->getLongName() . '.pdf';

        $this->mpdf = $mpdfProvider->getInstance();
        $this->mpdf->SetTitle($this->Entity->entityData['title']);
        $this->mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags'] ?? ''));

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
     * Generate pdf and return it as string
     */
    public function getFileContent(): string
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
     * Get the final html content with tex expressions converted in svg by tex2svg
     */
    public function getContent(): string
    {
        $Tex2Svg = new Tex2Svg($this->mpdf, $this->getHtml());
        return $Tex2Svg->getContent();
    }

    /**
     * Append PDFs attached to an entity
     */
    private function appendPdfs(array $pdfs): void
    {
        foreach ($pdfs as $pdf) {
            // There will be cases where the merging will fail
            // due to incompatibilities of Mpdf (actually fpdi) with the pdfs
            // See https://manuals.setasign.com/fpdi-manual/v2/limitations/
            // These cases will be caught and ignored
            try {
                $numberOfPages = $this->mpdf->setSourceFile($pdf[0]);

                for ($i = 1; $i <= $numberOfPages; $i++) {
                    // Import the ith page of the source PDF file
                    $page = $this->mpdf->importPage($i);

                    // getTemplateSize() is not documented in the MPDF manual
                    // @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
                    $pageDim = $this->mpdf->getTemplateSize($page);

                    if (is_array($pageDim)) { // satisfy phpstan
                        // add a new (blank) page with the dimensions of the imported page
                        $this->mpdf->AddPageByArray(array(
                            'orientation' => $pageDim['orientation'],
                            'sheet-size' => array($pageDim['width'], $pageDim['height']),
                        ));
                    }

                    // empty the header and footer
                    // cannot be an empty string
                    $this->mpdf->SetHTMLHeader(' ', '', true);
                    $this->mpdf->SetHTMLFooter(' ', '');

                    // add the content of the imported page
                    $this->mpdf->useTemplate($page);
                }
                // not all pdf will be able to be integrated, so for the one that will trigger an exception
            // we simply ignore it
            } catch (FpdiException) {
                continue;
            }
        }
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     */
    private function getHtml(): string
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
            'metadata' => $this->Entity->entityData['metadata'],
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

        $html = $this->getTwig(Config::getConfig())->render('pdf.html', $renderArr);

        // now remove any img src pointing to outside world
        // prevent blind ssrf (thwarted by CSP on webpage, but not in pdf)
        return preg_replace('/img src=("|\')(ht|f|)tp/i', 'nope', $html);
    }

    /**
     * Get a list of all PDFs that are attached to an entity
     *
     * @return array Empty or array of arrays with information for PDFs array('path/to/file', 'real.name')
     */
    private function getAttachedPdfs(): array
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
        // write content
        $this->mpdf->WriteHTML($this->getContent());

        if ($this->Entity->Users->userData['append_pdfs']) {
            $this->appendPdfs($this->getAttachedPdfs());
        }

        return $this->mpdf;
    }

    private function getBody(): string
    {
        $body = Tools::md2html($this->Entity->entityData['body'] ?? '');
        // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
        return str_replace('src="app/download.php?f=', 'src="' . dirname(__DIR__, 2) . '/uploads/', $body);
    }
}
