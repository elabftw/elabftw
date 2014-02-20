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
require_once 'inc/common.php';
require_once 'inc/head.php';
$page_title = 'Make zip';
require_once 'inc/menu.php';
require_once 'inc/info_box.php';
// Test if there is zip
if (!class_exists('ZipArchive')) {
    die("<p>You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in /etc/php/php.ini.</p>");
}

// Switch exp/items just for the table to search in sql requests
if ($_GET['type'] === 'exp') {
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $table = 'items';
} else {
    die('bad type');
}

// CREATE URL
$protocol = 'https://';
$url = $protocol.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];

// Check id is valid and assign it to $id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);
    // BEGIN ZIP
    // name of the downloadable file
    $zipname = kdate().".export.elabftw";

    $zipfile = 'uploads/export/'.$zipname."-".hash("sha512", uniqid(rand(), true)).".zip";

    $zip = new ZipArchive;
    $res = $zip->open($zipfile, ZipArchive::CREATE);
    if ($res === true) {
        foreach ($id_arr as $id) {
            // MAIN LOOP
            ////////////////

            // SQL to get info on the item we are zipping
            if ($table == 'experiments') {
                $sql = "SELECT * FROM experiments WHERE id = :id LIMIT 1";
            } else {
                $sql = "SELECT items.*,
                    items_types.name AS items_typesname
                    FROM items
                    LEFT JOIN items_types ON (items.type = items_types.id)
                    WHERE items.id = :id LIMIT 1";
            }
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->execute();
            $zipped = $req->fetch();
            $title = stripslashes($zipped['title']);
            // make a title without special char for folder inside .zip
            $clean_title = preg_replace('/[^A-Za-z0-9]/', ' ', $title);
            $date = $zipped['date'];
            // name of the folder
            // folder begin with date for experiments
            if ($table == 'experiments') {
                $folder = $date."-".$clean_title;
            } else { // items
                $type = $zipped['items_typesname'];
                $folder = $type." - ".$clean_title;
            }
            $body = stripslashes($zipped['body']);
            $req->closeCursor();

            // SQL to get firstname + lastname
            $sql = "SELECT firstname,lastname FROM users WHERE userid = ".$_SESSION['userid'];
            $req = $pdo->prepare($sql);
            $req->execute();
            $users = $req->fetch();
                $firstname = $users['firstname'];
                $lastname = $users['lastname'];
            // SQL to get tags
            $sql = "SELECT tag FROM ".$table."_tags WHERE item_id = $id";
            $req = $pdo->prepare($sql);
            $req->execute();
            $tags = null;
            while ($data = $req->fetch()) {
                $tags .= stripslashes($data['tag']).' ';
            }

            // SQL to get filesattached
            $sql = "SELECT * FROM uploads WHERE item_id = ".$id;
            $req = $pdo->prepare($sql);
            $req->execute();
            $real_name = array();
            $long_name = array();
            $comment = array();
            while ($uploads = $req->fetch()) {
                $real_name[] = $uploads['real_name'];
                $long_name[] = $uploads['long_name'];
                $comment[] = $uploads['comment'];
            }

            // create an html page
            $html = "<!DOCTYPE html><html><head><meta http-equiv='Content-Type' content='text/html'; charset='utf-8'><title>";
            $html .= $title;
            $html .= "</title></head><body>";
            $html .="<style type='text/css'>
                html {
                    background-color:#fff;
                }
                #container {
                    width:793px;
                    margin:auto;
                    padding:20px;
                    border: 2px solid green;
                }
                footer{
                    font-size:10px;
                }
            </style>";
            $html .= "<section id='container'>Date : ".$date."<br />
        <span style='text-align: right;'>By : ".$firstname." ".$lastname."</span><br />
        <div style='text-align: center;'><font size='10'>".$title."</font></span></div><br /><br />
        ".$body."<br />";
            // files attached ?
            $filenb = count($real_name);
            if ($filenb > 0) {
                $html .= "<section>";
                if ($filenb == 1) {
                    $html .= '
                        <h3>Attached file :</h3>';
                } else {
                    $html .= '<h3>Attached files :</h3>';
                }
                $html .= "<ul>";
                for ($i=0; $i<$filenb; $i++) {
                    $html .= "<li><a href='".$real_name[$i]."'>".$real_name[$i]."</a> (".stripslashes(str_replace("&#39;", "'", utf8_decode($comment[$i]))).").</li>";
                    // add files to archive
                    $zip->addFile('uploads/'.$long_name[$i], $folder."/".$real_name[$i]);
                }
                $html .= "
                    </ul>
                    </section>";

            }
            // GET LINKS
            // are we an experiment ?
            if ($table === 'experiments') {
                // has links ?
                $link_sql = "SELECT * FROM experiments_links WHERE item_id = ".$id;
                $link_req = $pdo->prepare($link_sql);
                $link_req->execute();
                while ($link_data = $link_req->fetch()) {
                    $link_id[] = $link_data['link_id'];
                }
                $linknb = $link_req->rowCount();
                if ($linknb > 0) {
                    $html .= "
                        <section>
                        <h3>Linked items :</h3>
                        <ul>";
                    // create url for database
                    $url = str_replace('make_zip.php', 'database.php', $url);
                    // put links in list with link to the url of item
                    for ($j=0; $j<$linknb; $j++) {
                        // get title and type of the item linked
                        $sql = "SELECT items.*,
                            items_types.name AS items_typesname
                            FROM items
                            LEFT JOIN items_types ON (items.type = items_types.id)
                            WHERE items.id = :id";
                        $item_req = $pdo->prepare($sql);
                        $item_req->execute(array(
                            'id' => $link_id[$j]
                        ));
                        $item_infos = $item_req->fetch();

                        $link_title = $item_infos['title'];
                        $link_type = $item_infos['items_typesname'];

                        $html .= "<li>[".$link_type."] - <a href='".$url."?mode=view&id=".$link_id[$j]."'>".$link_title."</a></li>";
                    }
                    $html .= "
                        </ul>
                        </section>";
                }
            }


            // FOOTER
            $html .= "~~~~<br />
                <footer>
            File created with <strong>elabFTW</strong> -- Free open source lab manager<br />
            <a href='http://www.elabftw.net'>eLabFTW.net</a>
                </footer>";
            $html .= "</section></body></html>";
            // CREATE HTML FILE
            // utf8 ftw
            $html = utf8_encode($html);
            // add header for utf-8
            $html = "\xEF\xBB\xBF".$html;
            $txtfile = 'uploads/export/'.'elabftw-'.uniqid();
            $tf = fopen($txtfile, 'w+');
            fwrite($tf, $html);
            fclose($tf);
            // add html file
            $zip->addFile($txtfile, $folder."/".$clean_title.".html");
            // add a PDF, too
            $pdfname = make_pdf($id, $table, 'uploads/export');
            $zip->addFile('uploads/export/'.$pdfname, $folder."/".$pdfname);
            // delete files
            //unlink($txtfile);
            //unlink('/tmp/'.$pdfname);

        } // end foreach
            $zip->close();

        // PAGE BEGIN
        echo "<div class='item'>";
        // Get zip size
        $zipsize = filesize($zipfile);
        // Get the title if there is only one experiment in the zip
        if (count($id_arr) === 1) {
            $zipname = $date."-".$clean_title;
        }
        // Display download link (with attribute type=zip for download.php)
        echo "<p>Your zip archive is ready, click to download <span class='filesize'>(".format_bytes($zipsize).")</span> :<br />
            <img src='themes/".$_SESSION['prefs']['theme']."/img/download.png' alt='download' /> 
            <a href='download.php?f=".$zipfile."&name=".$zipname.".zip&type=zip' target='_blank'>".$zipname.".zip</a></p>";
        echo "</div>";
    } else {
        echo 'Archive creation failed :(';
    }
    require_once('inc/footer.php');
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}
