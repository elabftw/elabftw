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

//function daydiff($date){
//// returns the number of days between now and a $date in epoch format
//$currtime =  time();
//$timediff = $currtime - $date;
//$daydiff = floor((($timediff / 60) /60) / 24);
//return $daydiff;
//}

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
 */

// replace br tags by new lines
function br2nl( $input ) {
     return preg_replace('/<br(\s+)?\/?>/i', "\n", $input);
}

// check if $int is a positive integer
function is_pos_int($int) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    return filter_var($int, FILTER_VALIDATE_INT, $filter_options);
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
function showXP($id, $display) {
// Show unique XP
    global $bdd;
    // SQL to get everything from selected id
    $sql = "SELECT id, title, date, body, outcome  FROM experiments WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $final_query = $req->fetch();
        if ($display === 'compact') {
            // COMPACT MODE //
            ?>
            <!-- BEGIN CONTENT -->
        <section onClick="window.open('experiments.php?mode=view&id=<?php echo $final_query['id'];?>', '_blank')" class="item">
            <?php
            echo "<span class='".$final_query['outcome']."_compact'>".$final_query['date']."</span> ";
            echo stripslashes($final_query['title']);
            echo "</section>";
        } else {
?>
        <section onClick="window.open('experiments.php?mode=view&id=<?php echo $final_query['id'];?>', '_blank')" class="item <?php echo $final_query['outcome'];?>">
    <?php
    // TAGS
    $tagsql = "SELECT tag FROM experiments_tags WHERE item_id = :id";
    $tagreq = $bdd->prepare($tagsql);
    $tagreq->execute(array(
        'id' => $final_query['id']
    ));
    echo "<span class='redo_compact'>".$final_query['date']."</span> ";
    echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
    while($tags = $tagreq->fetch()){
        echo "<a href='experiments.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
    }
    echo "</span>";
    // END TAGS
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
            <section onClick="window.open('database.php?mode=view&id=<?php echo $final_query['id'];?>', '_blank')" class='item'>
            <span class='date date_compact'><?php echo $final_query['date'];?></span>
            <span><?php echo stripslashes($final_query['title']);?>
            <!-- STAR RATING read only -->
            <?php show_stars($final_query['rating']) ?>
            </section>
<?php
        } else {
?>
        <section onClick="window.open('database.php?mode=view&id=<?php echo $final_query['id'];?>', '_blank')" class="item <?php echo $final_query['type'];?>">
        <?php
        // TAGS
        $tagsql = "SELECT tag FROM items_tags WHERE item_id = :id";
        $tagreq = $bdd->prepare($tagsql);
        $tagreq->execute(array(
            'id' => $final_query['id']
        ));
        echo "<span class='redo_compact'>".$final_query['date']."</span> ";
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $tagreq->fetch()){
            echo "<a href='database.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
        }
        echo "</span>";
        // END TAGS
        show_stars($final_query['rating']);
        echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
        echo "</section>";
        }
}

function check_body($input) {
    // Check BODY (sanitize only);
    if ((isset($input)) && (!empty($input))) {
        // we white list the allowed html tags
        return strip_tags($input, "<br><br /><p><sub><img><sup><strong><b><em><u><a><s><font><span><ul><li><ol><blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><td>");
    } else {
        return false;
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
    $title = $data['title'];
    $date = $data['date'];
    $body = $data['body'];
    if ($type == 'experiments') {
        $elabid = $data['elabid'];
    }
    $req->closeCursor();

    // SQL to get firstname + lastname
    $sql = "SELECT firstname,lastname FROM users WHERE userid = ".$data['userid'];
    $req = $bdd->prepare($sql);
    $req->execute();
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
        <hr>Made by : ".$firstname." ".$lastname;

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
        // we give the elabid as filename for experiments
        if ($type == 'experiments') {
            if ($out != 'browser') {
            return $elabid.'.pdf';
            $html2pdf->Output($out.'/'.$elabid.'.pdf', 'F');
            } else {
            $html2pdf->Output($elabid.'.pdf');
            }
        } else {
            if ($out != 'browser') {
            $html2pdf->Output($out.'/item.pdf', 'F');
            return 'item.pdf';
            } else {
            $html2pdf->Output('item.pdf');
            }
        }
    }

    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }
}
?>
