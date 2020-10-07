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

use function dirname;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use function file_exists;
use function is_dir;
use function mkdir;
use Mpdf\Mpdf;
use function preg_match;
use function str_replace;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMake
{
    /** @var string $longName a sha512 sum.pdf */
    public $longName;

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
     *
     * @return void
     */
    public function outputToFile(): void
    {
        $this->generate()->Output($this->filePath, 'F');
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     *
     * @return string
     */
    public function getContent(): string
    {
        $content = $this->buildHeader();
        $content .= $this->buildBody();
        $content .= $this->addLinkedItems();
        $content .= $this->addSteps();
        $content .= $this->addAttachedFiles();
        $content .= $this->addComments();
        $content .= $this->buildInfoBlock();

        return $content;
    }

    /**
     * Generate pdf and return it as string
     *
     * @return string
     */
    public function getPdf(): string
    {
        return $this->generate()->Output('', 'S');
    }

    /**
     * Replace weird characters by underscores
     *
     * @return string The file name of the pdf
     */
    public function getFileName(): string
    {
        $title = Filter::forFilesystem($this->Entity->entityData['title']);
        return $this->Entity->entityData['date'] . ' - ' . $title . '.pdf';
    }

    /**
     * Initialize Mpdf
     *
     * @return Mpdf
     */
    public function initializeMpdf(bool $multiEntity = false): Mpdf
    {
        $format = $this->Entity->Users->userData['pdf_format'];

        // we use a custom tmp dir, not the same as Twig because its content gets deleted after pdf is generated
        $tmpDir = \dirname(__DIR__, 2) . '/cache/mpdf/';
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
            $mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags']));
        }

        return $mpdf;
    }

    /**
     * Build the pdf
     *
     * @return Mpdf
     */
    private function generate(): Mpdf
    {
        $mpdf = $this->initializeMpdf();

        // write content
        $mpdf->WriteHTML($this->getContent());

        if ($this->Entity->Users->userData['pdfa']) {
            // make sure we can read the pdf in a long time
            // will embed the font and make the pdf bigger
            $mpdf->PDFA = true;
        }

        return $mpdf;
    }

    /**
     * Add the elabid block for an experiment
     *
     * @return string
     */
    private function addElabid(): string
    {
        if ($this->Entity instanceof Experiments) {
            return "<p class='elabid'>Unique eLabID: " . $this->Entity->entityData['elabid'] . '</p>';
        }
        return '';
    }

    /**
     * Add information about the lock state
     *
     * @return string
     */
    private function addLockinfo(): string
    {
        if ($this->Entity instanceof Experiments && $this->Entity->entityData['locked']) {
            // get info about the locker
            $Locker = new Users((int) $this->Entity->entityData['lockedby']);

            // separate the date and time
            $lockdate = explode(' ', $this->Entity->entityData['lockedwhen']);

            return "<p class='elabid'>locked by " . $Locker->userData['fullname'] . ' on ' .
                $lockdate[0] . ' at ' . $lockdate[1] . '</p>';
        }
        return '';
    }

    /**
     * Add the linked item if we are in an experiment
     *
     * @return string
     */
    private function addLinkedItems(): string
    {
        $html = '';
        $linksArr = $this->Entity->Links->read();
        if (empty($linksArr)) {
            return $html;
        }

        $html .= "<section class='no-break'>";
        $html .= '<h3>Linked item';
        if (count($linksArr) > 1) {
            $html .= 's';
        }
        $html .= ':</h3>';
        // add the item with a link

        // create Request object
        $Request = Request::createFromGlobals();
        $url = Tools::getUrl($Request) . '/database.php';

        foreach ($linksArr as $link) {
            $fullItemUrl = $url . '?mode=view&id=' . $link['itemid'];
            $html .= "<p class='pdf-ul'>";
            $html .= "<span style='color:#" . $link['color'] . "'>" .
                $link['name'] . "</span> - <a href='" . $fullItemUrl . "'>" . $link['title'] . '</a></p>';
        }

        return $html . '</section>';
    }

    /**
     * Add the comments (if any)
     *
     * @return string
     */
    private function addComments(): string
    {
        $html = '';

        $commentsArr = $this->Entity->Comments->read();
        if (empty($commentsArr)) {
            return $html;
        }
        $html .= "<section class='no-break'>";

        if (count($commentsArr) === 1) {
            $html .= '<h3>Comment:</h3>';
        } else {
            $html .= '<h3>Comments:</h3>';
        }

        foreach ($commentsArr as $comment) {
            $html .= "<p class='pdf-ul'>On " . $comment['datetime'] . ' ' . $comment['fullname'] . ' wrote :<br />';
            $html .= $comment['comment'] . '</p>';
        }

        return $html . '</section>';
    }

    /**
     * Load the contents of app/css/pdf.min.css and add to the content.
     *
     * @return string minified css for the pdf
     */
    private function addCss(): string
    {
        $css = file_get_contents(\dirname(__DIR__, 2) . '/web/app/css/pdf.min.css');
        if ($css === false) {
            throw new FilesystemErrorException('Cannot read the minified css file!');
        }
        return $css;
    }

    /**
     * Reference the attached files (if any) in the pdf
     * Add also the hash sum
     *
     * @return string
     */
    private function addAttachedFiles(): string
    {
        $html = '';

        // do nothing if we don't want the attached files
        if (!$this->Entity->Users->userData['inc_files_pdf']) {
            return $html;
        }

        $uploadsArr = $this->Entity->Uploads->readAll();
        $fileNb = count($uploadsArr);
        if ($fileNb > 0) {
            $html .= "<section class='no-break'>";
            if ($fileNb === 1) {
                $html .= '<h3>Attached file:</h3>';
            } else {
                $html .= '<h3>Attached files:</h3>';
            }

            foreach ($uploadsArr as $upload) {
                // the name of the file
                $html .= "<p class='pdf-ul'>" . $upload['real_name'];
                // add a comment ? don't add if it's the default text
                if ($upload['comment'] != 'Click to add a comment') {
                    $html .= ' (' . $upload['comment'] . ')';
                }
                // add hash ? don't add if we don't have it
                // length must be greater (sha2 hashes) or equal (md5) 32 bits
                if (\mb_strlen((string) $upload['hash']) >= 32) { // we have hash
                    $html .= '<br>' . $upload['hash_algorithm'] . ' : ' . $upload['hash'];
                }
                // if this is an image file, add the thumbnail picture
                $ext = Tools::getExt($upload['real_name']);
                $filePath = \dirname(__DIR__, 2) . '/uploads/' . $upload['long_name'];
                // if it's a TIF file, we can't add it like that to the pdf, but we can add the thumbnail
                if (preg_match('/(tiff|tif)$/i', $ext)) {
                    $filePath .= '_th.jpg';
                }
                if (file_exists($filePath) && preg_match('/(tiff|tif|jpg|jpeg|png|gif)$/i', $ext)) {
                    $html .= "<br /><img class='attached-image' src='" . $filePath . "' alt='attached image' />";
                }

                $html .= '</p>';
            }
            $html .= '</section>';
        }
        return $html;
    }

    /**
     * Add the steps
     *
     * @return string
     */
    private function addSteps(): string
    {
        $html = '';

        $stepsArr = $this->Entity->Steps->read();
        if (empty($stepsArr)) {
            return $html;
        }

        $html .= "<section class='no-break'>";
        $html .= '<h3>Steps:</h3>';

        foreach ($stepsArr as $step) {
            $html .= "<p class='pdf-ul'>" . $step['body'];
            if ($step['finished']) {
                $html .= ' (' . $step['finished_time'] . ')';
            }
            $html .= '</p>';
        }
        return $html . '</section>';
    }

    /**
     * A url to click is always nice
     *
     * @return string
     */
    private function addUrl(): string
    {
        $full_url = $this->getUrl();
        return "<p class='elabid'>link : <a href='" . $full_url . "'>" . $full_url . '</a></p>';
    }

    /**
     * Add the body
     *
     * @return string
     */
    private function buildBody(): string
    {
        $body = $this->Entity->entityData['body'];

        // convert to html if we have markdown
        if ($this->Entity->Users->userData['use_markdown']) {
            $body = Tools::md2html($body);
        }
        // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
        return str_replace('src="app/download.php?f=', 'src="' . \dirname(__DIR__, 2) . '/uploads/', $body);
    }

    /**
     * Build info box containing elabid and permalink
     *
     * @return string
     */
    private function buildInfoBlock(): string
    {
        return "<table id='infoblock'><tr><td class='noborder'>
            <barcode code='" . $this->getUrl() . "' type='QR' class='barcode' size='0.8' error='M' />
            </td><td class='noborder'>" .
            $this->addElabid() .
            $this->addLockinfo() .
            $this->addUrl() . '</td></tr></table>';
    }

    /**
     * Build the header of the HTML code that will be used to build the PDF.
     *
     * @return string
     */
    private function buildHeader(): string
    {
        $date = new \DateTime($this->Entity->entityData['date'] ?? Filter::kdate());

        // add a CJK font for the body if we want CJK fonts
        $cjkStyle = '';
        $cjkFont = '';
        if ($this->Entity->Users->userData['cjk_fonts']) {
            $cjkFont = 'font-family:sun-extA;';
            $cjkStyle = " style='" . $cjkFont . "'";
        }

        $pdfSig = '';
        $Request = Request::createFromGlobals();
        if ($Request->cookies->get('pdf_sig') === '1') {
            $pdfSig = "<div class='footer-block signatures'>
User's signature:<br><br>
Witness' name:<br><br>
Witness' signature:<br><br>
</div>";
        }

        // don't show the Tags line if there are none
        $tags = '';
        if ($this->Entity->entityData['tags']) {
            $tags = '<strong>Tags:</strong> <em>' .
                str_replace('|', ' ', $this->Entity->entityData['tags']) . '</em> <br />';
        }

        // we add a custom style for td for bug #350
        return '
<html>
    <head>
        <style>' . $this->addCss() . '</style>
        <style>td { ' . $cjkFont . ' }</style>
    </head>
<body' . $cjkStyle . '>
<htmlpageheader name="header">
    <div id="header">
        <h1>' . $this->Entity->entityData['title'] . '</h1>
        <p style="float:left; width:90%;">
            <strong>Date:</strong> ' . $date->format('Y-m-d') . '<br />' . $tags . '
            <strong>Created by:</strong> ' . $this->Entity->entityData['fullname'] . '
        </p>
        <p style="float:right; width:10%;"><br /><br />
            {PAGENO} / {nbpg}
        </p>
    </div>
</htmlpageheader>
<htmlpagefooter name="footer">' . $pdfSig . '
    <div class="footer-block footer">
        PDF generated with <a href="https://www.elabftw.net">elabftw</a>, a free and open source lab notebook
        <p style="font-size:6pt;">File generated on {DATE d-m-Y} at {DATE H:i}</p>
    </div>
</htmlpagefooter>
<sethtmlpageheader name="header" value="on" show-this-page="1" />
<sethtmlpagefooter name="footer" value="on" />
';
    }
}
