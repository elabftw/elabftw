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
use DateTimeImmutable;
use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Storage;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Changelog;
use Elabftw\Models\Config;
use Elabftw\Models\Notifications;
use Elabftw\Models\Users;
use Elabftw\Traits\TwigTrait;
use Elabftw\Traits\UploadTrait;
use function implode;
use League\Flysystem\Filesystem;
use Mpdf\Mpdf;
use setasign\Fpdi\FpdiException;
use function str_replace;
use function strtolower;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMakePdf
{
    use TwigTrait;
    use UploadTrait;

    public string $longName;

    public array $failedAppendPdfs = array();

    // collect paths of files to delete
    public array $trash = array();

    protected bool $includeAttachments = false;

    private FileSystem $cacheFs;

    private bool $pdfa;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     */
    public function __construct(MpdfProviderInterface $mpdfProvider, AbstractEntity $entity)
    {
        parent::__construct($mpdfProvider, $entity);

        $this->longName = $this->getLongName() . '.pdf';
        $this->pdfa = $mpdfProvider->isPdfa();

        $this->mpdf->SetTitle($this->Entity->entityData['title']);
        $this->mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags'] ?? ''));

        // suppress the "A non-numeric value encountered" error from mpdf
        // see https://github.com/baselbers/mpdf/commit
        // 5cbaff4303604247f698afc6b13a51987a58f5bc#commitcomment-23217652
        error_reporting(E_ERROR);

        $this->cacheFs = Storage::CACHE->getStorage()->getFs();
        if ($this->includeAttachments === false && $this->Entity->Users->userData['inc_files_pdf']) {
            $this->includeAttachments = true;
        }
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
        if ($this->errors && $this->notifications) {
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
        $now = new DateTimeImmutable();
        return ($this->Entity->entityData['date'] ?? $now->format('Y-m-d')) . ' - ' . $title . '.pdf';
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
        $this->contentSize = mb_strlen($content);
        return $content;
    }

    /**
     * Get a list of all PDFs that are attached to an entity
     *
     * @return array Empty or array of arrays with information for PDFs array('path/to/file', 'real_name')
     */
    public function getAttachedPdfs(): array
    {
        $uploadsArr = $this->Entity->entityData['uploads'];
        $listOfPdfs = array();

        if (empty($uploadsArr)) {
            return $listOfPdfs;
        }

        foreach ($uploadsArr as $upload) {
            $storageFs = Storage::from((int) $upload['storage'])->getStorage()->getFs();
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
        $date = new DateTimeImmutable($this->Entity->entityData['date'] ?? date('Ymd'));

        $locked = $this->Entity->entityData['locked'];
        $lockDate = '';
        $lockerName = '';

        if ($locked) {
            // get info about the locker
            $Locker = new Users((int) $this->Entity->entityData['lockedby']);
            $lockerName = $Locker->userData['fullname'];

            // separate the date and time
            $ldate = explode(' ', $this->Entity->entityData['locked_at']);
            $lockDate = $ldate[0] . ' at ' . $ldate[1];
        }

        // read the content of the thumbnail here to feed the template
        foreach ($this->Entity->entityData['uploads'] as $key => $upload) {
            $storageFs = Storage::from((int) $upload['storage'])->getStorage()->getFs();
            $thumbnail = $upload['long_name'] . '_th.jpg';
            // no need to filter on extension, just insert the thumbnail if it exists
            if ($storageFs->fileExists($thumbnail)) {
                $this->Entity->entityData['uploads'][$key]['base64_thumbnail'] = base64_encode($storageFs->read($thumbnail));
            }
        }

        $Changelog = new Changelog($this->Entity);

        $renderArr = array(
            'body' => $this->getBody(),
            'changes' => $Changelog->readAllWithAbsoluteUrls(),
            'css' => $this->getCss(),
            'date' => $date->format('Y-m-d'),
            'entityData' => $this->Entity->entityData,
            'includeChangelog' => $this->pdfa,
            'includeFiles' => $this->includeAttachments,
            'locked' => $locked,
            'lockDate' => $lockDate,
            'lockerName' => $lockerName,
            'pdfSig' => $this->Entity->Users->userData['pdf_sig'],
            'url' => $this->getURL(),
            'linkBaseUrl' => array(
                'items' => Config::fromEnv('SITE_URL') . '/database.php',
                'experiments' => Config::fromEnv('SITE_URL') . '/experiments.php',
            ),
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

    /**
     * Look for links to experiments or database made with the # autocompletion and thus relative.
     * We need to make them absolute or they will end up wrong.
     */
    private function fixLocalLinks(string $body): string
    {
        $matches = array();
        preg_match_all('/href="(experiments|database).php/', $body, $matches);
        $i = 0;
        foreach ($matches[0] as $match) {
            $body = str_replace($match, 'href="' . Config::fromEnv('SITE_URL') . '/' . $matches[1][$i] . '.php', $body);
            $i += 1;
        }
        return $body;
    }

    private function getBody(): string
    {
        $body = $this->Entity->entityData['body'] ?? '';
        if ($this->Entity->entityData['content_type'] === AbstractEntity::CONTENT_MD) {
            // md2html can result in invalid html, see https://github.com/elabftw/elabftw/issues/3076
            // the Filter::body (HTMLPurifier) rescues the invalid parts and thus avoids some MathJax errors
            // the consequence is a slightly different layout
            $body = Filter::body(Tools::md2html($body));
        }

        // now this part of the code will look for embeded images in the text and download them from storage and passes them to mpdf via variables.
        // it would have been preferable to avoid such complexity and regexes, but this is the most robust way to get images in there.
        // it works for any storage source
        // mpdf supports jpg, gif, png (+/- transparency), svg, webp, wmf and bmp (not documented but in source).
        // see https://mpdf.github.io/what-else-can-i-do/images.html
        // and https://github.com/mpdf/mpdf/blob/development/src/Image/ImageProcessor.php ImageProcessor::getImage() around line 218
        // and https://github.com/mpdf/mpdf/blob/development/src/Image/ImageTypeGuesser.php
        $matches = array();
        // ampersand (&) in html attributes is encoded (&amp;) so we need to use &amp; in the regex
        preg_match_all('/app\/download.php\?f=[[:alnum:]]{2}\/[[:alnum:]]{128}\.(?:jpe?g|gif|png|svg|webp|wmf|bmp)(?:&amp;storage=[0-9])?/i', $body, $matches);
        foreach ($matches[0] as $src) {
            // src will look like: app/download.php?f=c2/c2741a{...}016a3.png&amp;storage=1
            // so we parse it to get the file path and storage type
            $query = parse_url($src, PHP_URL_QUERY);
            if (!$query) {
                continue;
            }
            $res = array();
            parse_str($query, $res);
            // @phpstan-ignore-next-line
            $longname = (string) $res['f'];
            // there might be no storage value. In this case get it from the uploads table via the long name
            $storage = (int) ($res['amp;storage'] ?? $this->Entity->Uploads->getStorageFromLongname($longname));
            $storageFs = Storage::from($storage)->getStorage()->getFs();
            // pass image data to mpdf via variable. See https://mpdf.github.io/what-else-can-i-do/images.html#image-data-as-a-variable
            // avoid using data URLs (data:...) because it adds too many characters to $body, see https://github.com/elabftw/elabftw/issues/3627
            $this->mpdf->imageVars[$longname] = $storageFs->read($longname);
            $body = str_replace($src, 'var:' . $longname, $body);
        }

        return $this->fixLocalLinks($body);
    }
}
