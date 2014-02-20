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
function kdate()
{
    // returns today's date as YYYYMMDD format
    $today = getdate();
    $month = $today['mon'];
    // add 0 in front of month if needed
    if (strlen($month) === 1) {
        $month = '0'.$month;
    }
    $day = $today['mday'];
    // add 0 in front of day if needed
    if (strlen($day) === 1) {
        $day = '0'.$day;
    }
    return $today['year'].$month.$day;
}

function format_bytes($a_bytes)
{
    // nice display of filesize
    if ($a_bytes < 1024) {
        return $a_bytes .' B';
    } elseif ($a_bytes < 1048576) {
        return round($a_bytes / 1024, 2) .' KiB';
    } elseif ($a_bytes < 1073741824) {
        return round($a_bytes / 1048576, 2) . ' MiB';
    } elseif ($a_bytes < 1099511627776) {
        return round($a_bytes / 1073741824, 2) . ' GiB';
    } elseif ($a_bytes < 1125899906842624) {
        return round($a_bytes / 1099511627776, 2) .' TiB';
    } elseif ($a_bytes < 1152921504606846976) {
        return round($a_bytes / 1125899906842624, 2) .' PiB';
    } elseif ($a_bytes < 1180591620717411303424) {
        return round($a_bytes / 1152921504606846976, 2) .' EiB';
    } elseif ($a_bytes < 1208925819614629174706176) {
        return round($a_bytes / 1180591620717411303424, 2) .' ZiB';
    } else {
        return round($a_bytes / 1208925819614629174706176, 2) .' YiB';
    }
}

function createPassword($length)
{
    $password = "ChangeMe_";
    $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $i = 0;
    $random_part = "";
    while ($i < $length) {
        $random_part .= $chars{mt_rand(0, strlen($chars))};
        $i++;
    }
    $fullpassword = $password.$random_part;

    return $fullpassword;
}

function get_ext($filename)
{
    // Get file extension
    $path_info = pathinfo($filename);
    // if no extension
    if (!empty($path_info['extension'])) {
        return $path_info['extension'];
    }

    return false;
}


function make_thumb($src, $ext, $dest, $desired_width)
{
    // Create thumbnail from jpg, png or gif
    if ($ext === 'jpg' || $ext === 'JPEG' || $ext === 'JPG' || $ext === 'jpeg') {
        $source_image = imagecreatefromjpeg($src);
    } elseif ($ext === 'png') {
        $source_image = imagecreatefrompng($src);
    } elseif ($ext === 'gif') {
        $source_image = imagecreatefromgif($src);
    }
    $width = imagesx($source_image);
    $height = imagesy($source_image);

    // find the "desired height" of this thumbnail, relative to the desired width
    $desired_height = floor($height*($desired_width/$width));

    // create a new, "virtual" image
    $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

    // copy source image at a resized size
    imagecopyresized($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

    // create the physical thumbnail image to its destination (85% quality)
    imagejpeg($virtual_image, $dest, 85);
}

// check if $int is a positive integer
function is_pos_int($int)
{
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    return filter_var($int, FILTER_VALIDATE_INT, $filter_options);
}

function has_attachement($id)
{
    // Check if an item has a file attached
    global $pdo;
    $sql = "SELECT id FROM uploads 
        WHERE item_id = :item_id";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'item_id' => $id
    ));
    if ($req->rowCount() > 0) {
        return true;
    }

    return false;
}


// Search item
function search_item($type, $query, $userid)
{
    global $pdo;
    // we make an array for the resulting ids
    $results_arr = array();
    if ($type === 'xp') {
        // search in title date and body
        $sql = "SELECT id FROM experiments 
            WHERE userid = :userid AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%') LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        // put resulting ids in the results array
        while ($data = $req->fetch()) {
            $results_arr[] = $data['id'];
        }
        // now we search in tags, and append the found ids to our result array
        $sql = "SELECT item_id FROM experiments_tags WHERE userid = :userid AND tag LIKE '%$query%' LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        // now we search in file comments and filenames
        $sql = "SELECT item_id FROM uploads WHERE userid = :userid AND (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'experiment' LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        $req->closeCursor();

    } elseif ($type === 'db') {
        // search in title date and body
        $sql = "SELECT id FROM items 
            WHERE (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%') LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute();
        // put resulting ids in the results array
        while ($data = $req->fetch()) {
            $results_arr[] = $data['id'];
        }
        $req->closeCursor();
        // now we search in tags, and append the found ids to our result array
        $sql = "SELECT item_id FROM items_tags WHERE tag LIKE '%$query%' LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $_SESSION['userid']
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        // now we search in file comments and filenames
        $sql = "SELECT item_id FROM uploads WHERE (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'database' LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute();
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
            $req->closeCursor();
    } else {
        die('bad type : must be db or xp');
    }
    // filter out duplicate ids and reverse the order; XP should be sorted by date
    return $results_arr = array_reverse(array_unique($results_arr));
}

function show_tags($item_id, $table)
{
    // $table can be experiments_tags or items_tags
    global $pdo;
    // DISPLAY TAGS
    $sql = "SELECT tag FROM $table WHERE item_id = $item_id";
    $req = $pdo->prepare($sql);
    $req->execute();
    $tagcount = $req->rowCount();
    if ($tagcount > 0) {
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.png' alt='tags' /> ";
        while ($tags = $req->fetch()) {
            if ($table === 'experiments_tags') {
                echo "<a href='experiments.php?mode=show&tag=".urlencode(stripslashes($tags['tag']))."'>".stripslashes($tags['tag'])."</a> ";
            } else { // table is items_tags
                echo "<a href='database.php?mode=show&tag=".urlencode(stripslashes($tags['tag']))."'>".stripslashes($tags['tag'])."</a> ";
            }
        }
        echo "</span>";
    }
}

function showXP($id, $display)
{
// Show unique XP
    global $pdo;
    // SQL to get everything from selected id
    $sql = "SELECT experiments.*, status.color FROM
        experiments LEFT JOIN
        status ON (experiments.status = status.id)
        WHERE experiments.id = :id";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $experiments = $req->fetch();

    if ($display === 'compact') {
        // COMPACT MODE //
        echo "<section class='item' style='border: 1px solid #".$experiments['color']."'>";
        echo "<span class='date_compact'>".$experiments['date']."</span> ";
        echo stripslashes($experiments['title']);
        // view link
        echo "<a href='experiments.php?mode=view&id=".$experiments['id']."'>
            <img style='height:1em;float:right' src='img/view_compact.png' alt='view' title='view experiment' /></a>";
        echo "</section>";
    } else { // NOT COMPACT
        ?>
        <section class="item" style='border: 1px solid #<?php echo $experiments['color'];?>'>
        <?php
        // DATE
        echo "<span class='date'>".$experiments['date']."</span> ";
        // TAGS
        echo show_tags($id, 'experiments_tags');
        // view link
        echo "<a href='experiments.php?mode=view&id=".$experiments['id']."'>
            <img class='align_right' style='margin-left:5px;' src='img/arrow_right.png' alt='view' title='view experiment' /></a>";
        // show attached if there is a file attached
        if (has_attachement($experiments['id'])) {
            echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='file attached' />";
        }
        // show lock if item is locked on viewXP
        if ($experiments['locked'] == 1) {
            echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/lock.png' alt='lock' />";
        }
        echo "<p class='title'>". stripslashes($experiments['title']) . "</p>";
        echo "</section>";
    }
}
function show_stars($rating)
{
// a function to display the star ratings read only
// show_stars(3)
    echo "<div id='rating'>";
    if ($rating == 1) {
        echo "<img src='img/redstar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' />";
    }
    if ($rating == 2) {
        echo "<img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' />";
    }
    if ($rating == 3) {
        echo "<img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/greystar.gif' alt='1' /><img src='img/greystar.gif' alt='1' />";
    }
    if ($rating == 4) {
        echo "<img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/greystar.gif' alt='1' />";
    }
    if ($rating == 5) {
        echo "<img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' /><img src='img/redstar.gif' alt='1' />";
    }
    echo "</div>";
}

function showDB($id, $display)
{
// Show unique DB item
    global $pdo;
    // SQL to get everything from selected id
    $sql = "SELECT items.*,
        items_types.bgcolor,
        items_types.name
        FROM items
        LEFT JOIN items_types ON (items.type = items_types.id)
        WHERE items.id = :id";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $item = $req->fetch();
    if ($display === 'compact') {
        // COMPACT MODE //
        ?>
        <section class='item'>
            <span class='date date_compact'><?php echo $item['date'];?></span>
            <h4 style='border-right:1px dotted #ccd;color:#<?php echo $item['bgcolor'];?>'><?php echo $item['name'];?> </h4>
            <span style='margin-left:7px'><?php echo stripslashes($item['title']);?>
        <?php
        // view link
        echo "<a href='database.php?mode=view&id=".$item['id']."'>
        <img style='height:1em;float:right; margin-left:7px;' src='img/view_compact.png' alt='view' title='view item' /></a>";
        // STAR RATING read only
        show_stars($item['rating']);
        echo "</section>";

    } else { // NOT COMPACT

        echo "<section class='item'>";
        echo "<h4 style='color:#".$item['bgcolor']."'>".$item['name']." </h4>";
        // TAGS
        echo show_tags($id, 'items_tags');
        // view link
        echo "<a href='database.php?mode=view&id=".$item['id']."'>
        <img class='align_right' style='margin-left:5px;' src='img/arrow_right.png' alt='view' title='view item' /></a>";
        // STARS
        show_stars($item['rating']);
        // show attached if there is a file attached
        if (has_attachement($item['id'])) {
            echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='file attached' />";
        }
        echo "<p class='title'>". stripslashes($item['title']) . "</p>";
        echo "</section>";
    }
}

function check_title($input)
{
    // Check TITLE, what else ?
    if ((isset($input)) && (!empty($input))) {
        $title = filter_var($input, FILTER_SANITIZE_STRING);
        // remove linebreak to avoid problem in javascript link list generation on editXP
        return str_replace(array("\r\n", "\n", "\r"), ' ', $title);
    } else {
        return '';
    }
}

function check_date($input)
{
    // Check DATE (is != null ? is 8 in length ? is int ? is valable ?)
    if ((isset($input))
        && (!empty($input))
        && ((strlen($input) == '8'))
        && is_pos_int($input)) {
        // Check if day/month are good
        $datemonth = substr($input, 4, 2);
        $dateday = substr($input, 6, 2);
        if (($datemonth <= '12')
            && ($dateday <= '31')
            && ($datemonth > '0')
            && ($dateday > '0')) {
                // SUCCESS on every test
            return $input;
        } else {
            return kdate();
        }
    } else {
        return kdate();
    }
}

function check_body($input)
{
    // Check BODY (sanitize only);
    if ((isset($input)) && (!empty($input))) {
        // we white list the allowed html tags
        return strip_tags($input, "<br><br /><p><sub><img><sup><strong><b><em><u><a><s><font><span><ul><li><ol><blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><td>");
    } else {
        return '';
    }
}

function check_visibility($input)
{
    // Check VISIBILITY
    if ((isset($input)) && (!empty($input))) {
        if (($input === 'team')
        || ($input === 'user')) {
            return $input;
        }
    } else {
        // default is team
        return 'team';
    }
}

function make_pdf($id, $type, $out = 'browser')
{
    // make a pdf
    // $type can be 'experiments' or 'items'
    // $out is the output directory, 'browser' => pdf is downloaded (default), else it's written in the specified dir
    global $pdo;

    // SQL to get title, body and date
    $sql = "SELECT * FROM $type WHERE id = $id";
    $req = $pdo->prepare($sql);
    $req->execute();
    $data = $req->fetch();
    $title = stripslashes($data['title']);
    $date = $data['date'];
    // the name of the pdf is needed in make_zip
    $clean_title = $date."-".preg_replace('/[^A-Za-z0-9]/', ' ', $title);
    $body = stripslashes($data['body']);
    if ($type == 'experiments') {
        $elabid = $data['elabid'];
    }
    $req->closeCursor();

    // SQL to get firstname + lastname
    $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'userid' => $data['userid']
    ));
    $data = $req->fetch();
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $req->closeCursor();

    // SQL to get tags
    $sql = "SELECT tag FROM ".$type."_tags WHERE item_id = $id";
    $req = $pdo->prepare($sql);
    $req->execute();
    $tags = null;
    while ($data = $req->fetch()) {
        $tags .= $data['tag'].' ';
    }
    $req->closeCursor();

    // build content of page
    $content = "<h1>".$title."</h1><br />
        Date : ".$date."<br />
        <em>Keywords : ".$tags."</em><br />
        <hr>".$body."<br /><br />
        <hr>Made by : ".$firstname." ".$lastname."<br /><br />";
    // eLabFTW is only HTTPS
    $protocol = 'https://';
    $url = $protocol.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
    if ($type == 'experiments') {
        if ($out === 'browser') {
            $url = str_replace('make_pdf.php', 'experiments.php', $url);
        } else { // call from make_zip
            $url = str_replace('make_zip.php', 'experiments.php', $url);
        }
        $full_url = $url."?mode=view&id=".$id;
    // QR CODE (commented out as it's not possible with mpdf)
        //$content .= "<qrcode value='".$full_url."' ec='H' style='width: 42mm; background-color: white; color: black;'></qrcode>";
        $content .= "<br /><p>elabid : ".$elabid."</p>";
        $content .= "<p>URL : <a href='".$full_url."'>".$full_url."</a></p>";
    } else {
        if ($out === 'browser') {
            $url = str_replace('make_pdf.php', 'database.php', $url);
        } else { // call from make_zip
            $url = str_replace('make_zip.php', 'database.php', $url);
        }
        $full_url = $url."?mode=view&id=".$id;
        //$content .= "<qrcode value='".$full_url."' ec='H' style='width: 42mm; background-color: white; color: black;'></qrcode>";
        $content .= "<p>URL : <a href='".$full_url."'>".$full_url."</a></p>";
    }


    // Generate pdf with mpdf
    require_once 'lib/mpdf/mpdf.php';
    $mpdf = new mPDF();

    $mpdf->SetAuthor($firstname.' '.$lastname);
    $mpdf->SetTitle($title);
    $mpdf->SetSubject('eLabFTW pdf');
    $mpdf->SetKeywords($tags);
    $mpdf->WriteHTML($content);

    if ($type == 'experiments') {
        // used by make_zip
        if ($out != 'browser') {
            $mpdf->Output($out.'/'.$clean_title.'.pdf', 'F');
            return $clean_title.'.pdf';
        } else {
            $mpdf->Output($clean_title.'.pdf', 'I');
        }
    } else { // database item(s)
        // used by make_zip
        if ($out != 'browser') {
            $mpdf->Output($out.'/'.$clean_title.'.pdf', 'F');
            return $clean_title.'.pdf';
        } else {
            $mpdf->Output($clean_title.'.pdf', 'I');
        }
    }
}


function generate_elabid()
{
// Generate unique elabID
    $date = kdate();
    return $date."-".sha1(uniqid($date, true));
}

function duplicate_item($id, $type)
{
    global $pdo;
    if ($type === 'experiments') {
        $elabid = generate_elabid();

        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true LIMIT 1';
        $req = $pdo->prepare($sql);
        $req->execute();
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status LIMIT 1';
            $req = $pdo->prepare($sql);
            $req->execute();
            $status = $req->fetchColumn();
        }

        // SQL to get data from the experiment we duplicate
        $sql = "SELECT title, body, visibility FROM experiments WHERE id = ".$id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $data = $req->fetch();
        // SQL for duplicateXP
        $sql = "INSERT INTO experiments(title, date, body, status, elabid, visibility, userid) VALUES(:title, :date, :body, :status, :elabid, :visibility, :userid)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'title' => $data['title'],
            'date' => kdate(),
            'body' => $data['body'],
            'status' => $status,
            'elabid' => $elabid,
            'visibility' => $data['visibility'],
            'userid' => $_SESSION['userid']));
        // END SQL main


    }

    if ($type === 'items') {
        // SQL to get data from the item we duplicate
        $sql = "SELECT * FROM items WHERE id = ".$id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $data = $req->fetch();

        // SQL for duplicateDB
        $sql = "INSERT INTO items(title, date, body, userid, type) VALUES(:title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'title' => $data['title'],
            'date' => kdate(),
            'body' => $data['body'],
            'userid' => $_SESSION['userid'],
            'type' => $data['type']
        ));
        // END SQL main
    }

    // Get what is the id we just created
    $sql = "SELECT id FROM $type WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid']);
    $req->execute();
    $data = $req->fetch();
    $newid = $data['id'];


    if ($type === 'experiments') {
        // TAGS
        $sql = "SELECT tag FROM experiments_tags WHERE item_id = :id";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'id' => $id
        ));
        $tag_number = $req->rowCount();
        if ($tag_number > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $newid is the new exp created
                $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
                $reqtag = $pdo->prepare($sql);
                $result_tags = $reqtag->execute(array(
                    'tag' => $tags['tag'],
                    'item_id' => $newid,
                    'userid' => $_SESSION['userid']
                ));
            }
        } else { //no tag
            $result_tags = true;
        }
        // LINKS
        $linksql = "SELECT link_id FROM experiments_links WHERE item_id = :id";
        $linkreq = $pdo->prepare($linksql);
        $result_links = $linkreq->execute(array(
            'id' => $id
        ));
        while ($links = $linkreq->fetch()) {
            $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
            $req = $pdo->prepare($sql);
            $result_links = $req->execute(array(
                'link_id' => $links['link_id'],
                'item_id' => $newid
            ));
        }

        if ($result && $result_tags && $result_links) {
            return $newid;
        }

        return false;

    } else { // DB
        // TAGS
        $sql = "SELECT tag FROM items_tags WHERE item_id = ".$id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $tag_number = $req->rowCount();
        if ($tag_number > 0) {
            while ($tags = $req->fetch()) {
                // Put them in the new one. here $newid is the new exp created
                $sql = "INSERT INTO items_tags(tag, item_id) VALUES(:tag, :item_id)";
                $reqtag = $pdo->prepare($sql);
                $result_tags = $reqtag->execute(array(
                    'tag' => $tags['tag'],
                    'item_id' => $newid
                ));
            }
        }
        if ($result && $result_tags) {
            return $newid;
        }

        return false;
    }
}

// for displaying messages using jquery ui highlight/error messages
// call with display_message('info|error', $message);
function display_message($type, $message)
{
    if ($type === 'info') {

        echo "<div class='ui-state-highlight ui-corner-all' style='margin:5px'>
        <p><span class='ui-icon ui-icon-info' style='float: left; margin: 0 5px 0 5px;'></span>
        $message</p></div>";

    } elseif ($type === 'error') {

        echo "<div class='ui-state-error ui-corner-all' style='margin:5px'>
        <p><span class='ui-icon ui-icon-alert' style='float:left; margin: 0 5px 0 5px;'></span>
        $message</p></div>";
    }

    return false;
}

// to check if something is owned by a user before we add/delete/edit
function is_owned_by_user($id, $table, $userid)
{
    global $pdo;
    // type can be experiments or experiments_templates
    $sql = "SELECT userid FROM $table WHERE id = $id";
    $req = $pdo->prepare($sql);
    $req->execute();
    $result = $req->fetchColumn();

    if ($result === $userid) {
        return true;
    } else {
        return false;
    }
}

// return conf_value of asked conf_name
function get_config($conf_name)
{
    global $pdo;

    $sql = "SELECT * FROM config";
    $req = $pdo->prepare($sql);
    $req->execute();
    $config = $req->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
    return $config[$conf_name][0];
}

function check_executable($cmd)
{
    return shell_exec("which $cmd");
}

function custom_die()
{
    echo "
    <br />
    <br />
    </section>
    <br />
    <br />
    <footer>
    <p>Thanks for using eLabFTW :)</p>
    </footer>
    </body>
    </html>";
    die();
}
