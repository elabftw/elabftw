<?php
/**
 * \Elabftw\Elabftw\MakePdf
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \mPDF;
use \Exception;

/**
 * Create a pdf given an id and a type
 */
class MakePdf extends Make
{
    /** our favorite pdo object */
    private $pdo;

    /** the id of the item we want */
    private $id;
    /** 'experiments' or 'items' */
    protected $type;
    /** everything about the item */
    private $data;
    /** a formatted title for our pdf */
    private $cleanTitle;
    /** content of item */
    private $body;
    /** if we want to write it to a file */
    private $path;

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
     * @param int $id The id of the item we want
     * @param string $type 'experiments' or 'items'
     * @param string|null $path Path to where we want the pdf written
     */
    public function __construct($id, $type, $path = null)
    {
        $this->pdo = Db::getConnection();

        $this->id = $id;
        $this->validateId();

        // assign and check type
        $this->type = $this->checkType($type);

        // assign path
        $this->path = $path;

        // build the pdf content
        $this->initData();
        $this->setAuthor();
        $this->setCleanTitle();
        $this->setTags();
        $this->buildBody();
        $this->buildContent();

        // create the pdf
        $mpdf = new \mPDF();
        $mpdf->SetAuthor($this->author);
        $mpdf->SetTitle($this->title);
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetKeywords($this->tags);
        $mpdf->SetCreator('www.elabftw.net');
        $mpdf->WriteHTML($this->content);

        // output
        if (isset($this->path)) {
            $mpdf->Output($this->path, 'F');
        } else {
            $mpdf->Output($this->getCleanName(), 'I');
        }
    }

    /**
     * Validate the id we get.
     *
     * @throws Exception if id is bad
     */
    private function validateId()
    {
        if (!is_pos_int($this->id)) {
            throw new Exception('Bad id!');
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
     * Get data about the item we are pdf'ing
     *
     */
    private function initData()
    {
        // one cannot use placeholders as table or column identifiers in a prepared statement
        $sql = "SELECT * FROM " . $this->type . " WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $req->execute();
        $this->data = $req->fetch();
    }

    /**
     * Get firstname and lastname to put in pdf
     */
    private function setAuthor()
    {
        // SQL to get firstname + lastname
        $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->data['userid'], \PDO::PARAM_INT);
        $req->execute();
        $data = $req->fetch();

        $this->author = $data['firstname'] . ' ' . $data['lastname'];
    }

    /**
     * We want a title without weird characters
     */
    private function setCleanTitle()
    {
        $this->title = stripslashes($this->data['title']);
        $this->cleanTitle = $this->data['date'] . "-" . preg_replace('/[^A-Za-z0-9]/', '_', stripslashes($this->data['title']));
    }

    /**
     * Get the tags
     */
    private function setTags()
    {
        // SQL to get tags
        $sql = "SELECT tag FROM " . $this->type . "_tags WHERE item_id = :item_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id, \PDO::PARAM_INT);
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
        if ($this->type === 'experiments') {
            $this->content .= "<p class='elabid'>elabid : " . $this->data['elabid'] . "</p>";
        }
    }

    /**
     * Add information about the lock state
     */
    private function addLockinfo()
    {
        if ($this->data['locked'] == '1' && $this->type == 'experiments') {
            // get info about the locker
            $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid LIMIT 1";
            $reqlock = $this->pdo->prepare($sql);
            $reqlock->bindParam(':userid', $this->data['lockedby']);
            $reqlock->execute();
            $lockuser = $reqlock->fetch();

            // separate the date and time
            $lockdate = explode(' ', $this->data['lockedwhen']);
            $this->content .= "<p class='elabid'>locked by " . $lockuser['firstname'] . " " . $lockuser['lastname'] . " on " . $lockdate[0] . " at " . $lockdate[1] . ".</p>";
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
        $req->bindParam(':id', $this->id);
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
     * The css is added here directly instead of loading it from the css/pdf.css file
     * to avoid path problems
     * this css is the minified version of css/pdf.css
     */
    private function addCss()
    {
        $this->content .= "<style>a{color:#29AEB9;text-decoration:none}li,ul{color:#797979}.align_right{float:right}.align_left{text-align:left}.strong{font-weight:700}.three-columns{width:60%}.two-columns{-moz-columns:2 250px;-webkit-columns:2 250px;columns:2 250px}.column-left{float:left;width:20%}.column-right{float:right;width:20%}.column-center{display:inline-block;width:20%}p{color:#797979}p a{text-decoration:none}label{color:#797979;font-size:120%}hr{margin:10px 0;color:#dcdddc}li.inline{display:inline}div.txt ol li{list-style-type:decimal!important}div.txt li{list-style-type:square!important}div.txt table,div.txt table td{border:1px solid #000}h2{color:#797979;font-size:30px}h3{color:#797979;font-size:150%;margin:0 auto 10px}.mceditable{height:500px}h4{display:inline;font-size:110%;color:#797979}.inline{display:inline}img{border:none;position:relative;top:3px}section.item div.txt{overflow:hidden}.item{border:1px solid #dcdddc;border-radius:5px;margin:10px auto;padding:10px 0;overflow:hidden}.item a:hover{color:#29AEB9}.box{border:1px solid #dcdddc;border-radius:5px;padding:20px}.newexpcomment{background-color:#f2f2f2;border-radius:5px;color:#797979;margin:2px;padding:10px}.expcomment_box{background-color:#f2f2f2;border-radius:5px;margin-top:5px;padding:10px}.expcomment_box p{margin:5px 0;padding:5px;border-radius:5 0 5px;border-left:3px solid #797979}.expcomment_box p:hover{background-color:#555;color:#fff}.title{font-size:160%;margin:0;padding-left:20px}p.title{width:100%}.title_view{font-size:160%}.date,.date_compact{color:#5d5d5d;margin:15px auto;padding-left:20px}.date_view{padding-left:0}.date_compact{border-right:1px dotted #ccd}.tags{line-height:200%;margin:10px 0 10px 5px;padding:3px;width:90%}.tags a{text-decoration:none;color:#29AEB9}.tags a:hover{color:#343434}.tag a:hover{color:red}.tags_compact{background-color:#fff;border:1px solid #AAA;color:#000;border-radius:15px;font:12px Courier,Arial,sans-serif;line-height:200%;padding:5px;margin:10px 0 10px 25px}.tags_compact a{text-decoration:none}.tags_compact a:hover{color:red}#tagdiv{background-color:#fff;border:3px solid #CCC;padding:5px}.tag{font:700 13px Verdana,Arial,Helvetica,sans-serif;line-height:160%}.tag a{padding:5px;text-decoration:none}.smallgray{display:block;color:gray;font-size:80%}.filediv{margin-top:20px}.filediv a{font-size:14px;text-decoration:none}.filesize{color:grey;font-size:10px}.elabid{text-align:right;color:#797979;font-size:11px}code{border:1px dotted #ccc;padding:3px;background-color:#eee}footer{position:absolute;bottom:0;left:0;background-color:#e2e2e2;padding:10px 0;width:100%;font-size:80%;text-align:center}</style>";
    }

    /**
     * Reference the attached files (if any) in the pdf
     * Add also the md5 sum
     */
    private function addAttachedFiles()
    {
        // SQL to get attached files
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':type', $this->type);
        $req->execute();
        $real_name = array();
        $comment = array();
        $md5 = array();
        while ($uploads = $req->fetch()) {
            $real_name[] = $uploads['real_name'];
            $comment[] = $uploads['comment'];
            $md5[] = $uploads['md5'];
        }
        // do we have files attached ?
        if ($req->rowCount() > 0) {
            $this->content .= "<section>";
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
                // add md5 sum ? don't add if we don't have it
                if (strlen($md5[$i]) === 32) { // we have md5 sum
                    $this->content .= "<br>md5 : " . $md5[$i];
                }
                $this->content .= "</li>";
            }
            $this->content .= "</ul></section>";
        }
    }

    /**
     * A url to click is always nice
     */
    private function addUrl()
    {
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];

        if ($this->type === 'experiments') {
            $target = $this->type . '.php';
        } else {
            $target = 'database.php';
        }

        $url = str_replace(array('make_pdf.php', 'make.php', 'app/timestamp.php'), $target, $url);
        $full_url = $url . "?mode=view&id=" . $this->id;

        $this->content .= "<p class='elabid'>link : <a href='" . $full_url . "'>" . $full_url . "</a></p>";
    }

    /**
     * Add the linked item if we are in an experiment
     */
    private function addLinkedItems()
    {
        if ($this->type === 'experiments') {
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
            $req->bindParam(':item_id', $this->id);
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

                    $item_url = str_replace(array('make_pdf.php', 'make.php', 'app/timestamp.php'), 'database.php', $url);
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
        $this->body = str_replace("src=\"uploads/", "src=\"" . ELAB_ROOT . "uploads/", $this->data['body']);
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     */
    private function buildContent()
    {
        $this->addCss();
        $this->content .= "<h1 style='margin-bottom:5px'>" . stripslashes($this->data['title']) . "</h1>
            Date : " . Tools::formatDate($this->data['date']) . "<br />
            Tags : <em>". $this->tags . "</em><br />
            Made by : " . $this->author . "
            <hr>" . stripslashes($this->body);

        $this->addLinkedItems();
        $this->addAttachedFiles();
        $this->addComments();
        $this->addElabid();
        $this->addLockinfo();
        $this->addUrl();
        $this->content .= "<footer>PDF generated with <a href='http://www.elabftw.net'>elabftw</a>, a free and open source lab notebook</footer>";
    }
}
