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

use mPDF;

/**
 * Create a pdf given an id and a type
 */
class MakePdf extends Make
{
    /** our favorite pdo object */
    protected $pdo;
    /** Entity instance */
    private $Entity;

    /** 'experiments' or 'items' */
    protected $type;

    /** a formatted title for our pdf */
    private $cleanTitle;

    /** a sha512 sum */
    public $fileName;
    /** full path of file */
    public $filePath;

    /** who */
    public $author;
    /** raw title */
    public $title;
    /** list of tags */
    public $tags;
    /** the whole html string to write */
    public $content;


    /**
     * Everything is done in the constructor
     *
     * @param Entity $entity Experiments or Database
     * @param bool|null $toFile Do we want to write it to a file ?
     */
    public function __construct(Entity $entity, $toFile = false)
    {
        $this->pdo = Db::getConnection();
        $this->Entity = $entity;
        $this->Entity->canOrExplode('read');

        // build the pdf content
        $this->setAuthor();
        $this->setCleanTitle();
        $this->setTags();
        $this->buildContent();
        // create the pdf
        define("_MPDF_TEMP_PATH", ELAB_ROOT . 'uploads/tmp/');
        define("_MPDF_TTFONTDATAPATH", ELAB_ROOT . 'uploads/tmp/');
        $mpdf = new \mPDF('utf-8', 'A4');
        // make sure header and footer are not overlapping the body text
        $mpdf->setAutoTopMargin = 'stretch';
        $mpdf->setAutoBottomMargin = 'stretch';
        // set meta data
        $mpdf->SetAuthor($this->author);
        $mpdf->SetTitle($this->title);
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetKeywords($this->tags);
        $mpdf->SetCreator('www.elabftw.net');
        $mpdf->WriteHTML($this->content);

        // output
        if ($toFile) {
            $this->fileName = $this->getFileName() . '.pdf';
            $this->filePath = $this->getFilePath($this->fileName);
            $mpdf->Output($this->filePath, 'F');
        } else {
            $mpdf->Output($this->getCleanName(), 'I');
        }
    }

    /**
     * Cleantitle.pdf
     *
     * @return string The file name of the pdf
     */
    public function getCleanName()
    {
        return $this->cleanTitle . '.pdf';
    }

    /**
     * Get firstname and lastname to put in pdf
     */
    private function setAuthor()
    {
        // SQL to get firstname + lastname
        $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->Entity->entityData['userid'], \PDO::PARAM_INT);
        $req->execute();
        $data = $req->fetch();

        $this->author = $data['firstname'] . ' ' . $data['lastname'];
    }

    /**
     * We want a title without weird characters
     */
    private function setCleanTitle()
    {
        $this->title = stripslashes($this->Entity->entityData['title']);
        $this->cleanTitle = $this->Entity->entityData['date'] . "-" . preg_replace('/[^A-Za-z0-9]/', '_', stripslashes($this->Entity->entityData['title']));
    }

    /**
     * Get the tags
     */
    private function setTags()
    {
        // SQL to get tags
        $sql = "SELECT tag FROM " . $this->Entity->type . "_tags WHERE item_id = :item_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id);
        $req->execute();
        $this->tags = null;
        while ($data = $req->fetch()) {
            $this->tags .= $data['tag'] . ' ';
        }
        $req->closeCursor();
    }

    /**
     * Add the elabid block for an experiment
     */
    private function addElabid()
    {
        if ($this->Entity->type === 'experiments') {
            return "<p class='elabid'>elabid : " . $this->Entity->entityData['elabid'] . "</p>";
        }
    }

    /**
     * Add information about the lock state
     */
    private function addLockinfo()
    {
        if ($this->Entity->entityData['locked'] == '1' && $this->Entity->type == 'experiments') {
            // get info about the locker
            $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid LIMIT 1";
            $reqlock = $this->pdo->prepare($sql);
            $reqlock->bindParam(':userid', $this->Entity->entityData['lockedby']);
            $reqlock->execute();
            $lockuser = $reqlock->fetch();

            // separate the date and time
            $lockdate = explode(' ', $this->Entity->entityData['lockedwhen']);
            return "<p class='elabid'>locked by " . $lockuser['firstname'] . " " . $lockuser['lastname'] . " on " . $lockdate[0] . " at " . $lockdate[1] . ".</p>";
        }
    }

    /**
     * Add the comments (if any)
     */
    private function addComments()
    {
        // check if there is something to display first
        // get all comments, and infos on the commenter associated with this experiment
        $sql = "SELECT * FROM experiments_comments
            LEFT JOIN users ON (experiments_comments.userid = users.userid)
            WHERE exp_id = :id
            ORDER BY experiments_comments.datetime DESC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->execute();
        // if we have comments
        if ($req->rowCount() > 0) {
            $this->content .= "<section>";
            if ($req->rowCount() === 1) {
                $this->content .= "<h3>Comment :</h3>";
            } else {
                $this->content .= "<h3>Comments :</h3>";
            }
            // there is comments to display
            while ($comments = $req->fetch()) {
                if (empty($comments['firstname'])) {
                    $comments['firstname'] = '[deleted]';
                }
                $this->content .= "<p>On " . $comments['datetime'] . " " . $comments['firstname'] . " " . $comments['lastname'] . " wrote :<br />";
                $this->content .= "<p>" . $comments['comment'] . "</p>";

            }
            $this->content .= "</section>";
        }
    }

    /**
     * Load the contents of app/css/pdf.min.css and add to the content.
     */
    private function addCss()
    {
        return file_get_contents(ELAB_ROOT . 'app/css/pdf.min.css');
    }

    /**
     * Reference the attached files (if any) in the pdf
     * Add also the hash sum
     */
    private function addAttachedFiles()
    {
        // SQL to get attached files
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->bindParam(':type', $this->Entity->type);
        $req->execute();

        $real_name = array();
        $long_name = array();
        $comment = array();
        $hash = array();
        $hash_algorithm = array();

        while ($uploads = $req->fetch()) {
            $real_name[] = $uploads['real_name'];
            $long_name[] = $uploads['long_name'];
            $comment[] = $uploads['comment'];
            $hash[] = $uploads['hash'];
            $hash_algorithm[] = $uploads['hash_algorithm'];
        }
        // do we have files attached ?
        if ($req->rowCount() > 0) {
            $this->content .= "<section class='no_break'>";
            if ($req->rowCount() === 1) {
                $this->content .= "<h3>Attached file :</h3>";
            } else {
                $this->content .= "<h3>Attached files :</h3>";
            }
            $this->content .= "<ul>";
            $real_name_cnt = $req->rowCount();
            for ($i = 0; $i < $real_name_cnt; $i++) {
                $this->content .= "<li>" . $real_name[$i];
                // add a comment ? don't add if it's the default text
                if ($comment[$i] != 'Click to add a comment') {
                    $this->content .= " (" . stripslashes(htmlspecialchars_decode($comment[$i])) . ")";
                }
                // add hash ? don't add if we don't have it
                // length must be greater (sha2 hashes) or equal (md5) 32 bits
                if (strlen($hash[$i]) >= 32) { // we have hash
                    $this->content .= "<br>" . $hash_algorithm[$i] . " : " . $hash[$i];
                }
                // if this is an image file, add the thumbnail picture
                $ext = filter_var(Tools::getExt($real_name[$i]), FILTER_SANITIZE_STRING);
                $filepath = 'uploads/' . $long_name[$i];

                if (file_exists($filepath) && preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
                    $this->content .= "<br /><img class='attached_image' src='" . $filepath . "' alt='attached image' />";
                }
                $this->content .= "</li>";
            }
            $this->content .= "</ul></section>";
        }
    }

    /**
     * Return the url of the item or experiment
     *
     * @return string url to the item/experiment
     */
    private function getUrl()
    {
        // This is a workaround for PHP sometimes returning "localhost" in a LAN
        // environment. In that case, try to get the IP address to generate the correct
        // links.
        if ($_SERVER['SERVER_NAME'] === 'localhost') {
            $server_address = $_SERVER['SERVER_ADDR'];
        } else {
            $server_address = $_SERVER['SERVER_NAME'];
        }

        $url = 'https://' . $server_address . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        if ($this->Entity->type === 'experiments') {
            $target = $this->Entity->type . '.php';
        } else {
            $target = 'database.php';
        }

        $url = str_replace(array('make.php', 'app/controllers/ExperimentsController.php'), $target, $url);
        $full_url = $url . "?mode=view&id=" . $this->Entity->id;

        return $full_url;
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
     * Add the linked item if we are in an experiment
     */
    private function addLinkedItems()
    {
        if ($this->Entity->type === 'experiments') {
            // SQL to get linked items
            $sql = "SELECT experiments_links.*,
                experiments_links.link_id AS item_id,
                items.title AS title,
                items_types.name AS type
                FROM experiments_links
                LEFT JOIN items ON (experiments_links.link_id = items.id)
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE item_id = :item_id";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':item_id', $this->Entity->id);
            $req->execute();
            $links_id_arr = array();
            $links_title_arr = array();
            $links_type_arr = array();
            // we put what we need in arrays
            while ($links = $req->fetch()) {
                $links_id_arr[] = $links['item_id'];
                $links_title_arr[] = $links['title'];
                $links_type_arr[] = $links['type'];
            }
            // only display this section if there is something to display
            if ($req->rowCount() > 0) {
                $this->content .= '<section>';
                if ($req->rowCount() === 1) {
                    $this->content .= "<h3>Linked item :</h3>";
                } else {
                    $this->content .= "<h3>Linked items :</h3>";
                }
                $this->content .= "<ul>";
                $row_cnt = $req->rowCount();

                // add the item with a link
                $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
                for ($i = 0; $i < $row_cnt; $i++) {

                    $item_url = str_replace(array('make.php', 'app/controllers/ExperimentsController.php'), 'database.php', $url);
                    $full_item_url = $item_url . "?mode=view&id=" . $links_id_arr[$i];

                    $this->content .= "<li>[" . $links_type_arr[$i] . "] - <a href='" . $full_item_url . "'>" . $links_title_arr[$i] . "</a></li>";
                }
                $this->content .= "</ul></section>";
            }
        }
    }

    /**
     * We need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
     */
    private function buildBody()
    {
        $this->content .= str_replace("src=\"app/download.php?f=", "src=\"" . ELAB_ROOT . "uploads/", $this->Entity->entityData['body']);
    }

    /**
     * Build info box containing elabid and permalink
     */
    private function buildInfoBlock()
    {
        $this->content .= "<table id='infoblock'><tr><td class='noborder'>
                           <barcode code='" . $this->getUrl() . "' type='QR' class='barcode' size='0.8' error='M' />
                           </td><td class='noborder'>" . $this->addElabid() . $this->addLockinfo() . $this->addUrl() . "</td></tr>
                           </table>";
    }

    /**
     * Build the header of the HTML code that will be used to build the PDF.
     */
    private function buildHeader()
    {

        $date = date_create($this->Entity->entityData['date']);
        $date_str = date_format($date, 'Y-m-d');
        $header = '
                <html>
                    <head>
                        <style>' . $this->addCss() . '</style>
                    </head>
                <body>
                <htmlpageheader name="header">
                    <div id="header">
                        <h1>' . $this->Entity->entityData['title'] . '</h1>
                        <p style="float:left; width:90%;">
                            <strong>Date:</strong> ' . $date_str . '<br />
                            <strong>Tags:</strong> <em>' . $this->tags . '</em> <br />
                            <strong>Created by:</strong> ' . $this->author . '
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
                </htmlpagefooter>';

        $this->content .= $header;
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     */
    private function buildContent()
    {
        $this->buildHeader();
        $this->buildBody();
        $this->addLinkedItems();
        $this->addAttachedFiles();
        $this->addComments();
        $this->buildInfoBlock();
    }
}
