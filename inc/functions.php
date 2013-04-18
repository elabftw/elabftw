<?php
function kdate(){
    // returns today's date as YYMMDD format
    $today = getdate();
    $year = substr($today['year'], -2);
    $month = $today['mon'];
    if (strlen($month) === 1){
        $month = "0".$month;
    }
    $day = $today['mday'];
    if (strlen($day) === 1){
        $day = "0".$day;
    }
    return $year.$month.$day;
}

function format_bytes($a_bytes){
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

function createPassword($length) {
    $password = "ChangeMe_";
    $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $i = 0;
    $random_part = "";
    while ($i < $length) {
    $random_part .= $chars{mt_rand(0,strlen($chars))};
    $i++;
    }
    $fullpassword = $password.$random_part;
    return $fullpassword;
}

function get_ext($filename){
    // Get file extension
    $path_info = pathinfo($filename);
    // if no extension
    if (!empty($path_info['extension'])) {
        return $path_info['extension'];
    } else {
        return false;
    }
}


function make_thumb($src,$ext,$dest,$desired_width){
    // Create thumbnail from jpg, png or gif
    if($ext === 'jpg' || $ext === 'jpeg'){
        $source_image = imagecreatefromjpeg($src);
    }
    elseif($ext === 'png'){
        $source_image = imagecreatefrompng($src);
    }
    elseif($ext === 'gif'){
        $source_image = imagecreatefromgif($src);
    }
    $width = imagesx($source_image);
    $height = imagesy($source_image);

    // find the "desired height" of this thumbnail, relative to the desired width
    $desired_height = floor($height*($desired_width/$width));

    // create a new, "virtual" image
    $virtual_image = imagecreatetruecolor($desired_width,$desired_height);

    // copy source image at a resized size
    imagecopyresized($virtual_image,$source_image,0,0,0,0,$desired_width,$desired_height,$width,$height);

    // create the physical thumbnail image to its destination (85% quality)
    imagejpeg($virtual_image,$dest, 85);
}
/* unused
function loadClass($class) {
    require_once('lib/classes/'.$class.'.class.php');
}

// replace br tags by new lines
function br2nl( $input ) {
     return preg_replace('/<br(\s+)?\/?>/i', "\n", $input);
}

*/
// check if $int is a positive integer
function is_pos_int($int) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    return filter_var($int, FILTER_VALIDATE_INT, $filter_options);
}

function has_attachement($id) {
    // Check if an item has a file attached
    global $bdd;
    $sql = "SELECT id FROM uploads 
        WHERE item_id = :item_id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'item_id' => $id
    ));
    if ($req->rowCount() > 0) {
        return true;
    } else {
        return false;
    }
}


// Search item
function search_item($type, $query, $userid) {
    global $bdd;
    // we make an array for the resulting ids
    $results_arr = array();
    if($type === 'xp') {
    // search in title date and body
    $sql = "SELECT id FROM experiments 
        WHERE userid = :userid AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%') LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $userid
    ));
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['id'];
    }
    // now we search in tags, and append the found ids to our result array
    $sql = "SELECT item_id FROM experiments_tags WHERE userid = :userid AND tag LIKE '%$query%' LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $userid
    ));
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }
    // now we search in file comments and filenames
    $sql = "SELECT item_id FROM uploads WHERE userid = :userid AND (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'experiment' LIMIT 100";
    $req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($sql);
    $req->execute();
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['id'];
    }
    $req->closeCursor();
    // now we search in tags, and append the found ids to our result array
    $sql = "SELECT item_id FROM items_tags WHERE tag LIKE '%$query%' LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $_SESSION['userid']
    ));
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }
    // now we search in file comments and filenames
    $sql = "SELECT item_id FROM uploads WHERE (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'database' LIMIT 100";
    $req = $bdd->prepare($sql);
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
function show_tags($item_id, $table) {
    // $table can be experiments_tags or items_tags
    global $bdd;
    // DISPLAY TAGS
    $sql = "SELECT tag FROM $table WHERE item_id = $item_id";
    $req = $bdd->prepare($sql);
    $req->execute();
    $tagcount = $req->rowCount();
    if ($tagcount > 0) {
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $req->fetch()) {
            if ($table === 'experiments_tags') {
            echo "<a href='experiments.php?mode=show&tag=".urlencode(stripslashes($tags['tag']))."'>".stripslashes($tags['tag'])."</a> ";
            } else { // table is items_tags
            echo "<a href='database.php?mode=show&tag=".urlencode(stripslashes($tags['tag']))."'>".stripslashes($tags['tag'])."</a> ";
            }
        }
        echo "</span>";
    }
}

function showXP($id, $display) {
// Show unique XP
    global $bdd;
    // SQL to get everything from selected id
    $sql = "SELECT id, title, date, body, status, locked  FROM experiments WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $final_query = $req->fetch();
        if ($display === 'compact') {
            // COMPACT MODE //
            echo "<section class='item'>";
            echo "<span class='".$final_query['status']."_compact'>".$final_query['date']."</span> ";
            echo stripslashes($final_query['title']);
            // view link
            echo "<a href='experiments.php?mode=view&id=".$final_query['id']."'>
                <img class='align_right' src='img/view_compact.png' alt='view' title='view experiment' /></a>";
            echo "</section>";
        } else { // NOT COMPACT
?>
        <section class="item <?php echo $final_query['status'];?>">
    <?php
    // DATE
    echo "<span class='redo_compact'>".$final_query['date']."</span> ";
    // TAGS
    echo show_tags($id, 'experiments_tags');
    // view link
    echo "<a href='experiments.php?mode=view&id=".$final_query['id']."'>
        <img class='align_right' style='margin-left:5px;' src='img/view.png' alt='view' title='view experiment' /></a>";
    // show attached if there is a file attached
    if (has_attachement($final_query['id'])) {
        echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='file attached' />";
    }
    // show lock if item is locked on viewXP
    if ($final_query['locked'] == 1) {
        echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/lock.png' alt='lock' />";
    }
    echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
    echo "</section>";
        }
}
function show_stars($rating) {
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

/************************************
*************************************/
function get_item_info_from_id($id, $info) {
    global $bdd;
    $sql = "SELECT * FROM items_types WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $data = $req->fetch();
    return $data[$info];
}

function showDB($id, $display) {
// Show unique DB item
    global $bdd;
    // SQL to get everything from selected id
    $sql = "SELECT * FROM items WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $final_query = $req->fetch();
        if ($display === 'compact') {
            // COMPACT MODE //
            ?>
            <section class='item'>
            <h4 style='color:#<?php echo get_item_info_from_id($final_query['type'], 'bgcolor');?>'><?php echo get_item_info_from_id($final_query['type'], 'name');?> </h4>
            <span class='date date_compact'><?php echo $final_query['date'];?></span>
            <span><?php echo stripslashes($final_query['title']);?>
<?php
        // view link
    echo "<a href='database.php?mode=view&id=".$final_query['id']."'>
        <img class='align_right' style='margin-left:5px;' src='img/view_compact.png' alt='view' title='view item' /></a>";
        // STAR RATING read only
        show_stars($final_query['rating']);
        echo "</section>";

        } else { // NOT COMPACT

        echo "<section class='item'>";
        echo "<h4 style='color:#".get_item_info_from_id($final_query['type'], 'bgcolor')."'>".get_item_info_from_id($final_query['type'], 'name')." </h4>";
        // TAGS
        echo show_tags($id, 'items_tags');
        // view link
        echo "<a href='database.php?mode=view&id=".$final_query['id']."'>
        <img class='align_right' style='margin-left:5px;' src='img/view.png' alt='view' title='view item' /></a>";
        // STARS
        show_stars($final_query['rating']);
        // show attached if there is a file attached
        if (has_attachement($final_query['id'])) {
            echo "<img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='file attached' />";
        }
        echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
        echo "</section>";
        }
}

function check_title($input) {
    // Check TITLE, what else ?
    if ((isset($input)) && (!empty($input))) {
        $title = filter_var($input, FILTER_SANITIZE_STRING);
        // remove linebreak to avoid problem in javascript link list generation on editXP
        return str_replace(array("\r\n", "\n", "\r"), ' ', $title);
    } else {
        return '';
    }
}

function check_date($input) {
    // Check DATE (is != null ? is 6 in length ? is int ? is valable ?)
    if ((isset($input)) 
        && (!empty($input)) 
        && ((strlen($input) == "6")) 
        && is_pos_int($input)) {
        // Check if day/month are good
        $datemonth = substr($input,2,2);
        $dateday = substr($input,4,2);
        if(($datemonth <= "12") 
            && ($dateday <= "31") 
            && ($datemonth > "0") 
            && ($dateday > "0")){
                // SUCCESS on every test
        return $input;
        } else {
        return kdate();
        }
    } else {
        return kdate();
    }
}

function check_body($input) {
    // Check BODY (sanitize only);
    if ((isset($input)) && (!empty($input))) {
        // we white list the allowed html tags
        return strip_tags($input, "<br><br /><p><sub><img><sup><strong><b><em><u><a><s><font><span><ul><li><ol><blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><td>");
    } else {
        return '';
    }
}

function check_status($input) {
    // Check STATUS
    if ((isset($input)) 
        && (!empty($input))){
        if (($input === 'running')
        || ($input === 'success')
        || ($input === 'fail')
        || ($input === 'redo')) {
        return $input;
        }
    } else {
        return NULL;
    }
}


function make_pdf($id, $type, $out = 'browser') {
    // make a pdf
    // $type can be 'experiments' or 'items'
    // $out is the output directory, 'browser' => pdf is downloaded (default), else it's written in the specified dir
    global $bdd;

    // SQL to get title, body and date
    $sql = "SELECT * FROM $type WHERE id = $id";
    $req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $data['userid']
    ));
    $data = $req->fetch();
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $req->closeCursor();

    // SQL to get tags
    $sql = "SELECT tag FROM ".$type."_tags WHERE item_id = $id";
    $req = $bdd->prepare($sql);
    $req->execute();
    $tags = null;
    while($data = $req->fetch()){
        $tags .= $data['tag'].' ';
    }
    $req->closeCursor();

    // build content of page
    $content = "<h1>".$title."</h1><br />
        Date : ".$date."<br />
        <em>Keywords : ".$tags."</em><br />
        <hr>".$body."<br /><br />
        <hr>Made by : ".$firstname." ".$lastname."<br /><br />";
    // QR CODE
    if (!empty($_SERVER['HTTPS'])) {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $url = $protocol.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
    if ($type == 'experiments') {
        if ($out === 'browser') { 
        $url = str_replace('make_pdf.php', 'experiments.php', $url);
        } else { // call from make_zip
        $url = str_replace('make_zip.php', 'experiments.php', $url);
        }
        $full_url = $url."?mode=view&id=".$id;
        $content .= "<qrcode value='".$full_url."' ec='H' style='width: 42mm; background-color: white; color: black;'></qrcode>";
        $content .= "<br /><p>elabid : ".$elabid."</p>";
        $content .= "<p>URL : <a href='".$full_url."'>".$full_url."</a></p>";
    } else {
        if ($out === 'browser') { 
        $url = str_replace('make_pdf.php', 'database.php', $url);
        } else { // call from make_zip
        $url = str_replace('make_zip.php', 'database.php', $url);
        }
        $full_url = $url."?mode=view&id=".$id;
        $content .= "<qrcode value='".$full_url."' ec='H' style='width: 42mm; background-color: white; color: black;'></qrcode>";
        $content .= "<p>URL : <a href='".$full_url."'>".$full_url."</a></p>";
    }


    // convert in PDF with html2pdf
    require_once('lib/html2pdf/html2pdf.class.php');
    try
    {
        $html2pdf = new HTML2PDF('P', 'A4', 'fr');
        $html2pdf->pdf->SetAuthor($firstname.' '.$lastname);
        $html2pdf->pdf->SetTitle($title);
        $html2pdf->pdf->SetSubject('eLabFTW pdf');
        $html2pdf->pdf->SetKeywords($tags);
        $html2pdf->setDefaultFont('Arial');
        $html2pdf->writeHTML($content);

        if ($type == 'experiments') {
            // used by make_zip
            if ($out != 'browser') {
            $html2pdf->Output($out.'/'.$clean_title.'.pdf', 'F');
            return $clean_title.'.pdf';
            } else {
            $html2pdf->Output($clean_title.'.pdf');
            }
        } else { // database item(s)
            // used by make_zip
            if ($out != 'browser') {
            $html2pdf->Output($out.'/'.$clean_title.'.pdf', 'F');
            return $clean_title.'.pdf';
            } else {
            $html2pdf->Output($clean_title.'.pdf');
            }
        }
    }

    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }
}

function generate_elabid() {
// Generate unique elabID
    $date = kdate();
    return $date."-".sha1(uniqid($date, TRUE));
}

function feeling_lucky($q, $type) {
    // get query, search and return to viewitem page with first result
    global $bdd;
    $q = filter_var($q, FILTER_SANITIZE_STRING);
    if ($type == 'experiments') {
        $sql = "SELECT * FROM experiments WHERE userid = :userid AND (title LIKE '%$q%' OR date LIKE '%$q%' OR body LIKE '%$q%') LIMIT 1";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'userid' => $_SESSION['userid']
        ));
    } else {
        $sql = "SELECT * FROM items WHERE (title LIKE '%$q%' OR date LIKE '%$q%' OR body LIKE '%$q%') LIMIT 1";
        $req = $bdd->prepare($sql);
        $req->execute();
    }
    $count = $req->rowCount();
    if ($count > 0) {
        $data = $req->fetch();
        header('Location: experiments.php?mode=view&id='.$data['id']);
    } else {
        $msg_arr = array();
        $msg_arr[] = "Nothing found with this query :/";
        $_SESSION['infos'] = $msg_arr;
        header('Location: search.php');
    }
}

function duplicate_item($id, $type) {
    global $bdd;
    if ($type === 'experiments') {
        $elabid = generate_elabid();
        // SQL to get data from the experiment we duplicate
        $sql = "SELECT title, body FROM experiments WHERE id = ".$id;
        $req = $bdd->prepare($sql);
        $req->execute();
        $data = $req->fetch();
        // SQL for duplicateXP
        $sql = "INSERT INTO experiments(title, date, body, status, elabid, userid) VALUES(:title, :date, :body, :status, :elabid, :userid)";
        $req = $bdd->prepare($sql);
        $result = $req->execute(array(
            'title' => $data['title'],
            'date' => kdate(),
            'body' => $data['body'],
            'status' => 'running',
            'elabid' => $elabid,
            'userid' => $_SESSION['userid']));
        // END SQL main


    }

    if ($type === 'items') {
        // SQL to get data from the item we duplicate
        $sql = "SELECT * FROM items WHERE id = ".$id;
        $req = $bdd->prepare($sql);
        $req->execute();
        $data = $req->fetch();

        // SQL for duplicateDB
        $sql = "INSERT INTO items(title, date, body, userid, type) VALUES(:title, :date, :body, :userid, :type)";
        $req = $bdd->prepare($sql);
        $result = $req->execute(array(
            'title' => $data['title'],
            'date' => kdate(),
            'body' => $data['body'],
            'userid' => $_SESSION['userid'],
            'type' => $data['type']
        ));
        // END SQL main
    }

    // Get what is the experiment id we just created
    $sql = "SELECT id FROM ".$type." WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
    $req = $bdd->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid']);
    $req->execute();
    $data = $req->fetch();
    $newid = $data['id'];


    if ($type === 'experiments') {
        // TAGS
        $sql = "SELECT tag FROM experiments_tags WHERE item_id = ".$id;
        $req = $bdd->prepare($sql);
        $req->execute();
        while($tags = $req->fetch()){
            // Put them in the new one. here $newid is the new exp created
            $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
            $reqtag = $bdd->prepare($sql);
            $result_tags = $reqtag->execute(array(
                'tag' => $tags['tag'],
                'item_id' => $newid,
                'userid' => $_SESSION['userid']
            ));
        }
        // LINKS
        $linksql = "SELECT link_id FROM experiments_links WHERE item_id = ".$id;
        $linkreq = $bdd->prepare($linksql);
        $result_links = $linkreq->execute();
        while($links = $linkreq->fetch()) {
            $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
            $req = $bdd->prepare($sql);
            $result_links = $req->execute(array(
                'link_id' => $links['link_id'],
                'item_id' => $newid
            ));
        }
        if($result && $result_tags && $result_links) {
            return $newid;
        } else {
            return false;
        }
    } else { // DB
        // TAGS
        $sql = "SELECT tag FROM items_tags WHERE item_id = ".$id;
        $req = $bdd->prepare($sql);
        $req->execute();
        while($tags = $req->fetch()){
            // Put them in the new one. here $newid is the new exp created
            $sql = "INSERT INTO items_tags(tag, item_id) VALUES(:tag, :item_id)";
            $reqtag = $bdd->prepare($sql);
            $result_tags = $reqtag->execute(array(
                'tag' => $tags['tag'],
                'item_id' => $newid
            ));
        }
        if($result && $result_tags) {
            return $newid;
        } else {
            return false;
        }
    }
}
/*
function get_export_path() {
    // are we on windows ?
    if (PHP_OS == 'Windows' || PHP_OS == 'WIN32' || PHP_OS == 'WINNT') {
        return "uploads\\export\\";
    } else {
        return "uploads/export/";
    }
}
 */
?>
