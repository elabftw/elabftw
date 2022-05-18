<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function date;
use DateTime;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Notifications;
use Elabftw\Models\Users;
use Elabftw\Traits\PdfTrait;
use Elabftw\Traits\TwigTrait;
use Elabftw\Traits\UploadTrait;
use function implode;
use League\Flysystem\Filesystem;
use Mpdf\Mpdf;
use setasign\Fpdi\FpdiException;
use const SITE_URL;
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

    public array $failedAppendPdfs = array();

    // collect paths of files to delete
    public array $trash = array();

    // switch to disable notifications from within class
    // if notifications are handled by calling class
    public bool $createNotifications = true;

    private FileSystem $cacheFs;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     */
    public function __construct(MpdfProviderInterface $mpdfProvider, AbstractEntity $entity)
    {
        parent::__construct($entity);

        $this->longName = $this->getLongName() . '.pdf';

        $this->mpdf = $mpdfProvider->getInstance();
        $this->mpdf->SetTitle($this->Entity->entityData['title']);
        $this->mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags'] ?? ''));

        // suppress the "A non-numeric value encountered" error from mpdf
        // see https://github.com/baselbers/mpdf/commit
        // 5cbaff4303604247f698afc6b13a51987a58f5bc#commitcomment-23217652
        error_reporting(E_ERROR);

        $this->cacheFs = (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs();
    }

    public function __destruct()
    {
        // delete the temporary files once we're done with it
        foreach ($this->trash as $filename) {
            $this->cacheFs->delete($filename);
        }
    }

    /**
     * Generate pdf and return it as string
     */
    public function getFileContent(): string
    {
        $output = $this->generate()->Output('', 'S');
        if ($this->errors && $this->createNotifications) {
            $Notifications = new Notifications($this->Entity->Users);
            $Notifications->create(new CreateNotificationParams(Notifications::PDF_GENERIC_ERROR));
        }
        return $output;
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
        $content = $Tex2Svg->getContent();

        // Inform user that there was a problem with Tex rendering
        if ($Tex2Svg->mathJaxFailed) {
            $this->errors[] = array(
                'type' => Notifications::MATHJAX_FAILED,
                'body' => array(
                    'entity_id' => $this->Entity->id,
                    'entity_page' => $this->Entity->page,
                ),
            );
        }
        return $content;
    }

    /**
     * Get a list of all PDFs that are attached to an entity
     *
     * @return array Empty or array of arrays with information for PDFs array('path/to/file', 'real_name')
     */
    public function getAttachedPdfs(): array
    {
        $uploadsArr = $this->Entity->Uploads->readAllNormal();
        $listOfPdfs = array();

        if (empty($uploadsArr)) {
            return $listOfPdfs;
        }

        foreach ($uploadsArr as $upload) {
            $storageFs = (new StorageFactory((int) $upload['storage']))->getStorage()->getFs();
            if ($storageFs->fileExists($upload['long_name']) && strtolower(Tools::getExt($upload['real_name'])) === 'pdf') {
                // the real_name is used in case of error appending it
                // the content is stored in a temporary file so it can be read with appendPdfs()
                $tmpPath = FsTools::getCacheFile();
                $filename = basename($tmpPath);
                $this->cacheFs->writeStream($filename, $storageFs->readStream($upload['long_name']));
                $listOfPdfs[] = array($tmpPath, $upload['real_name']);
                // add the temporary file to the trash
                $this->trash[] = $filename;
            }
        }

        return $listOfPdfs;
    }

    /**
     * Append PDFs attached to an entity
     */
    public function appendPdfs(array $pdfs, ?Mpdf $mpdf = null): void
    {
        $mpdf = $mpdf ?? $this->mpdf;
        foreach ($pdfs as $pdf) {
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
                // not all pdf will be able to be integrated, so for the one that will trigger an exception
            // we simply ignore it and collect information for notification
            } catch (FpdiException) {
                // collect real name of attached pdf
                $this->failedAppendPdfs[] = $pdf[1];
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

        $date = new DateTime($this->Entity->entityData['date'] ?? date('Ymd'));

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

        // read the content of the thumbnail here to feed the template
        $uploadsArr =  $this->Entity->Uploads->readAllNormal();
        foreach ($uploadsArr as $key => $upload) {
            $storageFs = (new StorageFactory((int) $upload['storage']))->getStorage()->getFs();
            $thumbnail = $upload['long_name'] . '_th.jpg';
            // no need to filter on extension, just insert the thumbnail if it exists
            if ($storageFs->fileExists($thumbnail)) {
                $uploadsArr[$key]['base64_thumbnail'] = base64_encode($storageFs->read($thumbnail));
            }
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
            'uploadsArr' => $uploadsArr,
            'url' => $this->getURL(),
            'linkBaseUrl' => SITE_URL . '/database.php',
            'useCjk' => $this->Entity->Users->userData['cjk_fonts'],
        );

        return $this->getTwig(Config::getConfig())->render('pdf.html', $renderArr);
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
            if ($this->failedAppendPdfs) {
                $this->errors[] = array(
                    'type' => Notifications::PDF_APPENDMENT_FAILED,
                    'body' => array(
                        'entity_id' => $this->Entity->id,
                        'entity_page' => $this->Entity->page,
                        'file_names' => implode(', ', $this->failedAppendPdfs),
                    ),
                );
            }
        }
        return $this->mpdf;
    }

    private function getBody(): string
    {
        $body = Tools::md2html($this->Entity->entityData['body'] ?? '');
        // md2html can result in invalid html, see https://github.com/elabftw/elabftw/issues/3076
        // the next line (HTMLPurifier) rescues the invalid parts and thus avoids some MathJax errors
        // the consequence is a slightly different layout
        $body = Filter::body($body);

        // now this part of the code will look for embeded images in the text and download them from storage and insert them as base64
        // it would have been preferable to avoid such complexity and regexes, but this is the most robust way to get images in there.
        // it works for gif png jpg images from any storage source
        $matches = array();
        // for some reason &storage=[0-9] didn't work so we match .{14}
        // u is a modifier for multibyte/utf8 support. So why 14 and not 10 you might ask? Dunno. It works.
        preg_match_all('/app\/download.php\?f=[[:alnum:]]{2}\/[[:alnum:]]{128}\.(?:png|jpeg|jpg|gif).{14}/u', $body, $matches);
        foreach ($matches[0] as $src) {
            // src will look like: app/download.php?f=c2/c2741a{...}016a3.png&storage=1
            // so we parse it to get the file path and storage type
            $query = parse_url($src, PHP_URL_QUERY);
            if (!$query) {
                continue;
            }
            $res = array();
            parse_str($query, $res);
            $storage = (int) $res['amp;storage'];
            $storageFs = (new StorageFactory($storage))->getStorage()->getFs();
            $encoded = base64_encode($storageFs->read($res['f']));
            // get filetype based on extension so we can declare correctly the type of image
            $body = str_replace($src, 'data:image/' . Tools::getMimeExt($res['f']) . ';base64,' . $encoded, $body);
        }
        return $body;
    }
}
