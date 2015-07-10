<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
namespace Elabftw\Elabftw;

class MakePdf
{

    private $id;
    private $type;
    private $data;
    private $cleanTitle;
    private $body;

    public $author;
    public $title;
    public $tags;
    public $content;


    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;

        $this->initData();
        $this->setAuthor();
        $this->setCleanTitle();
        $this->setTags();
        $this->buildBody();
        $this->buildContent();
    }

    public function getFileName()
    {
        return $this->cleanTitle . '.pdf';
    }

    private function initData()
    {
        global $pdo;

        // title, date, body, elabid, userid, lock
        $sql = "SELECT * FROM " . $this->type . " WHERE id = " . $this->id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->data = $req->fetch();
    }

    private function setAuthor()
    {
        global $pdo;

        // SQL to get firstname + lastname
        $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $this->data['userid']
        ));
        $data = $req->fetch();

        $this->author = $data['firstname'] . ' ' . $data['lastname'];
    }

    private function setCleanTitle()
    {
        $this->title = stripslashes($this->data['title']);
        $this->cleanTitle = $this->data['date'] . "-" . preg_replace('/[^A-Za-z0-9]/', '_', stripslashes($this->data['title']));
    }

    private function setTags()
    {

        global $pdo;
        // SQL to get tags
        $sql = "SELECT tag FROM " . $this->type . "_tags WHERE item_id = " . $this->id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->tags = null;
        while ($data = $req->fetch()) {
            $this->tags .= $data['tag'] . ' ';
        }
        $req->closeCursor();
    }

    public function getTags()
    {
        return $this->tags;
    }

    private function addElabid()
    {

        // ELABID
        if ($this->type === 'experiments') {
            $this->content .= "<p class='elabid'>elabid : " . $this->data['elabid'] . "</p>";
        }
    }

    private function addLockinfo()
    {

        // LOCK BLOCK
        if ($this->data['locked'] == '1' && $this->type == 'experiments') {
            global $pdo;
            // get info about the locker
            $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid LIMIT 1";
            $reqlock = $pdo->prepare($sql);
            $reqlock->execute(array(
                'userid' => $this->data['lockedby']
            ));
            $lockuser = $reqlock->fetch();

            // separate date and time
            if (isset($this->data['lockedwhen'])) {
                $lockdate = explode(' ', $this->data['lockedwhen']);
                $this->content .= "<p class='elabid'>locked by " . $lockuser['firstname'] . " " . $lockuser['lastname'] . " on " . $lockdate[0] . " at " . $lockdate[1] . ".</p>";
            }
        }
    }

    private function addComments()
    {
        global $pdo;

        // SQL to get comments
        // check if there is something to display first
        // get all comments, and infos on the commenter associated with this experiment
        $sql = "SELECT * FROM experiments_comments
            LEFT JOIN users ON (experiments_comments.userid = users.userid)
            WHERE exp_id = :id
            ORDER BY experiments_comments.datetime DESC";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'id' => $this->id
        ));
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

    // the css is added here directly instead of loading it from the css/pdf.css file
    // to avoid path problems
    // this css is the minified version of css/pdf.css
    private function addCss()
    {

        $this->content .= "<style>a{color:#29AEB9;text-decoration:none}li,ul{color:#797979}.align_right{float:right}.align_left{text-align:left}.strong{font-weight:700}.three-columns{width:60%}.two-columns{-moz-columns:2 250px;-webkit-columns:2 250px;columns:2 250px}.column-left{float:left;width:20%}.column-right{float:right;width:20%}.column-center{display:inline-block;width:20%}p{color:#797979}p a{text-decoration:none}label{color:#797979;font-size:120%}hr{margin:10px 0;color:#dcdddc}li.inline{display:inline}div.txt ol li{list-style-type:decimal!important}div.txt li{list-style-type:square!important}div.txt table,div.txt table td{border:1px solid #000}h2{color:#797979;font-size:30px}h3{color:#797979;font-size:150%;margin:0 auto 10px}.mceditable{height:500px}h4{display:inline;font-size:110%;color:#797979}.inline{display:inline}img{border:none;position:relative;top:3px}section.item div.txt{overflow:hidden}.item{border:1px solid #dcdddc;border-radius:5px;margin:10px auto;padding:10px 0;overflow:hidden}.item a:hover{color:#29AEB9}.box{border:1px solid #dcdddc;border-radius:5px;padding:20px}.newexpcomment{background-color:#f2f2f2;border-radius:5px;color:#797979;margin:2px;padding:10px}.expcomment_box{background-color:#f2f2f2;border-radius:5px;margin-top:5px;padding:10px}.expcomment_box p{margin:5px 0;padding:5px;border-radius:5 0 5px;border-left:3px solid #797979}.expcomment_box p:hover{background-color:#555;color:#fff}.title{font-size:160%;margin:0;padding-left:20px}p.title{width:100%}.title_view{font-size:160%}.date,.date_compact{color:#5d5d5d;margin:15px auto;padding-left:20px}.date_view{padding-left:0}.date_compact{border-right:1px dotted #ccd}.tags{line-height:200%;margin:10px 0 10px 5px;padding:3px;width:90%}.tags a{text-decoration:none;color:#29AEB9}.tags a:hover{color:#343434}.tag a:hover{color:red}.tags_compact{background-color:#fff;border:1px solid #AAA;color:#000;border-radius:15px;font:12px Courier,Arial,sans-serif;line-height:200%;padding:5px;margin:10px 0 10px 25px}.tags_compact a{text-decoration:none}.tags_compact a:hover{color:red}#tagdiv{background-color:#fff;border:3px solid #CCC;padding:5px}.tag{font:700 13px Verdana,Arial,Helvetica,sans-serif;line-height:160%}.tag a{padding:5px;text-decoration:none}.smallgray{display:block;color:gray;font-size:80%}.filediv{margin-top:20px}.filediv a{font-size:14px;text-decoration:none}.filesize{color:grey;font-size:10px}.elabid{text-align:right;color:#797979;font-size:11px}code{border:1px dotted #ccc;padding:3px;background-color:#eee}footer{position:absolute;bottom:0;left:0;background-color:#e2e2e2;padding:10px 0;width:100%;font-size:80%;text-align:center}</style>";

    }

    private function addAttachedFiles()
    {
        global $pdo;
        // ATTACHED FILES
        // SQL to get attached files
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $pdo->prepare($sql);
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
        if (count($real_name) > 0) {
            $this->content .= "<section>";
            if (count($real_name) === 1) {
                $this->content .= "<h3>Attached file :</h3>";
            } else {
                $this->content .= "<h3>Attached files :</h3>";
            }
            $this->content .= "<ul>";
            $real_name_cnt = count($real_name);
            for ($i = 0; $i < $real_name_cnt; $i++) {
                $this->content .= "<li>" . $real_name[$i];
                // add a comment ? don't add if it's the default text
                if ($comment[$i] != 'Click to add a comment') {
                    $this->content .= " (" . stripslashes(htmlspecialchars_decode($comment[$i])) . ")";
                }
                // add md5 sum ? don't add if we don't have it
                if (strlen($md5[$i]) == '32') { // we have md5 sum
                    $this->content .= "<br>md5 : " . $md5[$i];
                }
                $this->content .= "</li>";
            }
            $this->content .= "</ul></section>";
        }
    }

    private function addUrl()
    {
        // Construct URL
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];

        if ($this->type === 'experiments') {
            if (preg_match('/make_pdf/', $url)) {
                $url = str_replace('make_pdf.php', 'experiments.php', $url);
            } else { // call from make_zip or timestamp.php
                $url = str_replace(array('make_zip.php', 'app/timestamp.php'), 'experiments.php', $url);
            }
        } else { //item
            if (preg_match('/make_pdf/', $url)) {
                $url = str_replace('make_pdf.php', 'database.php', $url);
            } else { // call from make_zip
                $url = str_replace('make_zip.php', 'database.php', $url);
            }
        }
        $full_url = $url . "?mode=view&id=" . $this->id;
        $this->content .= "<p class='elabid'>link : <a href='" . $full_url . "'>" . $full_url . "</a></p>";
    }

    private function addLinkedItems()
    {
        if ($this->type === 'experiments') {
            global $pdo;

            // SQL to get linked items
            $sql = "SELECT experiments_links.*,
                experiments_links.link_id AS item_id,
                items.title AS title,
                items_types.name AS type
                FROM experiments_links
                LEFT JOIN items ON (experiments_links.link_id = items.id)
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE item_id = ".$this->id;
            $req = $pdo->prepare($sql);
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

                    $item_url = str_replace(array('make_pdf.php', 'make_zip.php', 'app/timestamp.php'), 'database.php', $url);
                    $full_item_url = $item_url . "?mode=view&id=" . $links_id_arr[$i];

                    $this->content .= "<li>[" . $links_type_arr[$i] . "] - <a href='" . $full_item_url . "'>" . $links_title_arr[$i] . "</a></li>";
                }
                $this->content .= "</ul></section>";
            }
        }
    }

    // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
    private function buildBody()
    {
        $this->body = str_replace("src=\"uploads/", "src=\"" . ELAB_ROOT . "uploads/", $this->data['body']);
    }

    private function buildContent()
    {
        // build HTML content that will be fed to mpdf->WriteHTML()
        $this->addCss();
        $this->content .= "<h1 style='margin-bottom:5px'>" . stripslashes($this->data['title']) . "</h1>
            Date : ".format_date($this->data['date']) . "<br />
            Tags : <em>".$this->tags . "</em><br />
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
