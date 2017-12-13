<?php
/**
 * \Elabftw\Elabftw\MakePdf
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Mpdf\Mpdf;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMake
{
    /** @var string $fileName a sha512 sum */
    public $fileName;

    /** @var string $filePath the full path of the file */
    public $filePath;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     */
    public function __construct(AbstractEntity $entity)
    {
        parent::__construct($entity);
        // suppress the "A non-numeric value encountered" error from mpdf
        // see https://github.com/baselbers/mpdf/commit
        // 5cbaff4303604247f698afc6b13a51987a58f5bc#commitcomment-23217652
        error_reporting(E_ERROR);

    }

    /**
     * Build content and output something
     *
     * @param bool|null $toFile Do we want to write it to a file ?
     * @param bool $timestamp Is it a timestamp pdf we are doing ? If yes save it in normal path, not tmp
     */
    public function output($toFile = false, $timestamp = false)
    {
        $format = $this->Entity->Users->userData['pdf_format'];

        // we use a custom tmp dir, not the same as Twig because its content gets deleted after pdf is generated
        $tmpDir = ELAB_ROOT . 'uploads/tmp/mpdf/';
        if (!is_dir($tmpDir)) {
            if (!mkdir($tmpDir)) {
                throw new Exception("Could not create the $tmpDir directory. Please check permissions on this folder.");
            }
        }

        // create the pdf
        $mpdf = new Mpdf(array(
            'format' => $format,
            'tempDir' => $tmpDir,
            'mode' => 'utf-8'
        ));

        // make sure header and footer are not overlapping the body text
        $mpdf->setAutoTopMargin = 'stretch';
        $mpdf->setAutoBottomMargin = 'stretch';

        // set metadata
        $mpdf->SetAuthor($this->Entity->entityData['fullname']);
        $mpdf->SetTitle($this->Entity->entityData['title']);
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetKeywords(strtr($this->Entity->entityData['tags'], '|', ' '));
        $mpdf->SetCreator('www.elabftw.net');

        // write content
        $mpdf->WriteHTML($this->getContent());

        if ($this->Entity->Users->userData['pdfa']) {
            // make sure we can read the pdf in a long time
            // will embed the font and make the pdf bigger
            $mpdf->PDFA = true;
        }

        // output
        if ($toFile) {
            $this->fileName = $this->getUniqueString() . '.pdf';

            // output in tmp folder if it's not a timestamp pdf
            if ($timestamp) {
                $this->filePath = $this->getUploadsPath() . $this->fileName;
            } else {
                $this->filePath = $this->getTmpPath() . $this->fileName;
            }
            $mpdf->Output($this->filePath, 'F');
        } else {
            $mpdf->Output($this->getCleanName(), 'I');
        }
    }

    /**
     * Add the elabid block for an experiment
     *
     * @return string
     */
    private function addElabid()
    {
        if ($this->Entity instanceof Experiments) {
            return "<p class='elabid'>Unique eLabID: " . $this->Entity->entityData['elabid'] . "</p>";
        }
        return "";
    }

    /**
     * Add information about the lock state
     *
     * @return string
     */
    private function addLockinfo()
    {
        if ($this->Entity instanceof Experiments && $this->Entity->entityData['locked']) {
            // get info about the locker
            $Locker = new Users($this->Entity->entityData['lockedby']);

            // separate the date and time
            $lockdate = explode(' ', $this->Entity->entityData['lockedwhen']);

            return "<p class='elabid'>locked by " . $Locker->userData['fullname'] . " on " .
                $lockdate[0] . " at " . $lockdate[1] . "</p>";
        }
        return "";
    }

    /**
     * Add the linked item if we are in an experiment
     */
    private function addLinkedItems()
    {
        $html = '';
        $linksArr = $this->Entity->Links->readAll();
        if ($linksArr === false) {
            return $html;
        }

        $html .= "<section class='no-break'>";
        $html .= "<h3>Linked item";
        if (count($linksArr) > 1) {
            $html .= 's';
        }
        $html .= ":</h3>";
        // add the item with a link

        // create Request object
        $Request = Request::createFromGlobals();
        $url = Tools::getUrl($Request) . '/' . $this->Entity->page . '.php';
        // not pretty but gets the job done
        $url = str_replace('app/classes/', '', $url);

        foreach ($linksArr as $link) {
            $fullItemUrl = $url . "?mode=view&id=" . $link['link_id'];
            $html .= "<p class='pdf-ul'>";
            $html .= "<span style='color:#" . $link['color'] . "'>" .
                $link['name'] . "</span> - <a href='" . $fullItemUrl . "'>" . $link['title'] . "</a></p>";
        }
        $html .= "</section>";

        return $html;
    }

    /**
     * Add the comments (if any)
     *
     * @return string
     */
    private function addComments()
    {
        $html = '';
        // will return false if empty
        $commentsArr = $this->Entity->Comments->readAll();
        if ($commentsArr === false) {
            return $html;
        }
        $html .= "<section class='no-break'>";

        if (count($commentsArr) === 1) {
            $html .= "<h3>Comment:</h3>";
        } else {
            $html .= "<h3>Comments:</h3>";
        }

        foreach ($commentsArr as $comment) {
            $html .= "<p class='pdf-ul'>On " . $comment['datetime'] . " " . $comment['fullname'] . " wrote :<br />";
            $html .= $comment['comment'] . "</p>";
        }

        $html .= "</section>";

        return $html;
    }

    /**
     * Load the contents of app/css/pdf.min.css and add to the content.
     *
     * @return string
     */
    private function addCss()
    {
        return file_get_contents(ELAB_ROOT . 'app/css/pdf.min.css');
    }

    /**
     * Reference the attached files (if any) in the pdf
     * Add also the hash sum
     *
     * @return string
     */
    private function addAttachedFiles()
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
                $html .= "<h3>Attached file:</h3>";
            } else {
                $html .= "<h3>Attached files:</h3>";
            }

            foreach ($uploadsArr as $upload) {
                // the name of the file
                $html .= "<p class='pdf-ul'>" . $upload['real_name'];
                // add a comment ? don't add if it's the default text
                if ($upload['comment'] != 'Click to add a comment') {
                    $html .= " (" . $upload['comment'] . ")";
                }
                // add hash ? don't add if we don't have it
                // length must be greater (sha2 hashes) or equal (md5) 32 bits
                if (strlen($upload['hash']) >= 32) { // we have hash
                    $html .= "<br>" . $upload['hash_algorithm'] . " : " . $upload['hash'];
                }
                // if this is an image file, add the thumbnail picture
                $ext = filter_var(Tools::getExt($upload['real_name']), FILTER_SANITIZE_STRING);
                $filePath = 'uploads/' . $upload['long_name'];
                if (file_exists($filePath) && preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
                    $html .= "<br /><img class='attached-image' src='" . $filePath . "' alt='attached image' />";
                }

                $html .= "</p>";
            }
            $html .= "</section>";
        }
        return $html;
    }

    /**
     * A url to click is always nice
     */
    private function addUrl()
    {
        $full_url = $this->getUrl();
        return "<p class='elabid'>link : <a href='" . $full_url . "'>" . $full_url . "</a></p>";
    }

    /**
     * Add the body
     *
     * @return string
     */
    private function buildBody()
    {
        $body = $this->Entity->entityData['body'];

        // convert to html if we have markdown
        if ($this->Entity->Users->userData['use_markdown']) {
            $body = Tools::md2html($body);
        }
        // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
        return str_replace("src=\"app/download.php?f=", "src=\"" . ELAB_ROOT . "uploads/", $body);
    }

    /**
     * Build info box containing elabid and permalink
     *
     * @return string
     */
    private function buildInfoBlock()
    {
        return "<table id='infoblock'><tr><td class='noborder'>
                           <barcode code='" . $this->getUrl() . "' type='QR' class='barcode' size='0.8' error='M' />
                           </td><td class='noborder'>" .
                           $this->addElabid() .
                           $this->addLockinfo() .
                           $this->addUrl() . "</td></tr></table>";
    }

    /**
     * Build the header of the HTML code that will be used to build the PDF.
     */
    private function buildHeader()
    {

        $date = date_create($this->Entity->entityData['date']);
        $date_str = date_format($date, 'Y-m-d');

        // add a CJK font for the body if we want CJK fonts
        $cjkStyle = "";
        $cjkFont = "";
        if ($this->Entity->Users->userData['cjk_fonts']) {
            $cjkFont = "font-family:sun-extA;";
            $cjkStyle = " style='" . $cjkFont . "'";
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
            <strong>Date:</strong> ' . $date_str . '<br />
            <strong>Tags:</strong> <em>' .
                strtr($this->Entity->entityData['tags'], '|', ' ') . '</em> <br />
            <strong>Created by:</strong> ' . $this->Entity->entityData['fullname'] . '
        </p>
        <p style="float:right; width:10%;"><br /><br />
            {PAGENO} / {nbpg}
        </p>
    </div>
</htmlpageheader>
<htmlpagefooter name="footer">
    <div id="footer">
        PDF generated with <a href="https://www.elabftw.net">elabftw</a>, a free and open source lab notebook
        <p style="font-size:6pt;">File generated on {DATE d-m-Y} at {DATE H:m}</p>
    </div>
</htmlpagefooter>
';
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     *
     * @return string
     */
    private function getContent()
    {
        $content = $this->buildHeader();
        $content .= $this->buildBody();
        if ($this->Entity instanceof Experiments) {
            $content .= $this->addLinkedItems();
            $content .= $this->addComments();
        }
        $content .= $this->addAttachedFiles();
        $content .= $this->buildInfoBlock();

        return $content;
    }

    /**
     * Replace weird characters by underscores
     *
     * @return string The file name of the pdf
     */
    public function getCleanName()
    {
        return $this->Entity->entityData['date'] . "-" .
            preg_replace('/[^A-Za-z0-9 ]/', '_', $this->Entity->entityData['title']) . '.pdf';
    }
}
