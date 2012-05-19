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
require_once("themes/".$_SESSION['prefs']['theme']."/highlight.css");
?>
<div id='submenu'>
<form id='big_search' method='get' action='experiments.php'>
<input type='search' name='q' size='50' placeholder='Type your search' />
</form>
<br />
<a href="create_item.php?type=exp"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Create experiment</a> | 
<a href='#' class='trigger'><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/duplicate.png" alt="" /> Create from template</a>
</div><!-- end submenu -->
<div class='toggle_container'><ul>
<? // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
$tplreq = $bdd->prepare($sql);
$tplreq->execute();
$count_tpl = $tplreq->rowCount();
if ($count_tpl > 0) {
    while ($tpl = $tplreq->fetch()) {
        echo "<li class='inline'><a href='create_item.php?type=exp&tpl=".$tpl['id']."' class='templates'>".$tpl['name']."</a></li> ";
    }
} else { // user has no templates
    echo "You do not have any templates yet. Go to <a href='ucp.php'>your control panel</a> to make one !";
}
?>
</ul></div><br />
<?php
// VIEWING PREFS //
$display = $_SESSION['prefs']['display'];
$order = $_SESSION['prefs']['order'];
$sort = $_SESSION['prefs']['sort'];
$limit = $_SESSION['prefs']['limit'];

// OFFSET
if ((!isset($_GET['offset'])) || (empty($_GET['offset']))) {
    $offset = '0';
} elseif (filter_var($_GET['offset'], FILTER_VALIDATE_INT)){
    $offset = $_GET['offset'];
} else {
    die("<p>What are you doing, Dave ?</p>");
}

// Check CURRENTPAGE
if ((!isset($_GET['currentpage'])) || (empty($_GET['currentpage']))) {
    // $currentpage must start at 0 to have $offset = 0
    $currentpage = '0';
} elseif ((filter_var($_GET['currentpage'], FILTER_VALIDATE_INT) && ($_GET['currentpage'] > 0))){
    $currentpage = $_GET['currentpage'];
} else {
    $currentpage = 0;
}
// for pagination
$offset = $currentpage * $limit;

// SQL for showXP
// reminder : order by and sort must be passed to the prepare(), not during execute()
// /////////////////
// SEARCH
// /////////////////
if (isset($_GET['q'])) { // if there is a query
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    // we make an array for the resulting ids
    $results_arr = array();
    // search in title date and body
    $sql = "SELECT id FROM experiments 
        WHERE userid = :userid AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%') LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $_SESSION['userid']
    ));
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['id'];
    }
    // now we search in tags, and append the found ids to our result array
    $sql = "SELECT item_id FROM experiments_tags WHERE userid = :userid AND tag LIKE '%$query%' LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $_SESSION['userid']
    ));
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }
    // now we search in file comments and filenames
    $sql = "SELECT item_id FROM uploads WHERE userid = :userid AND (comment LIKE '%$query%' OR real_name LIKE '%$query%') LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $_SESSION['userid']
    ));
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }
    $req->closeCursor();

    // filter out duplicate ids
    $results_arr = array_unique($results_arr);
    // show number of results found
    if (count($results_arr) > 1){
        echo "Found ".count($results_arr)." results.";
    } elseif (count($results_arr) == 1){
        echo "Found 1 result.";
    } else {
        echo "Nothing found :(";
    }

    // loop the results array and display results
    foreach($results_arr as $result_id) {
        // SQL to get everything from selected id
        $sql = "SELECT id, title, date, body, outcome  FROM experiments WHERE id = :id";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $result_id
        ));
        $final_query = $req->fetch();
        ?>
        <section OnClick="document.location='experiments.php?mode=view&id=<?php echo $final_query['id'];?>'" class="item <?php echo $final_query['outcome'];?>">
        <?php
        // TAGS
        $tagsql = "SELECT tag FROM experiments_tags WHERE item_id = ".$final_query['id'];
        $tagreq = $bdd->prepare($tagsql);
        $tagreq->execute();
        echo "<span class='redo_compact'>".$final_query['date']."</span> ";
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $tagreq->fetch()){
            echo "<a href='experiments.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
        }
        echo "</span>";
        // END TAGS
        echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
        echo "</section>";
    } // end foreach
///////// END SEARCH
// /////////////
// RELATED
// /////////////
} elseif (isset($_GET['related'])){// search for related experiments to DB item id
    $item_id = is_pos_int($_GET['related']);
    // we make an array for the resulting ids
    $results_arr = array();
    // search in title date and body
    $sql = "SELECT id FROM experiments 
        WHERE item = :item_id LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'item_id' => $item_id
    ));
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['id'];
    }
    $req->closeCursor();
    // show number of results found
    if (count($results_arr) > 1){
        echo "Found ".count($results_arr)." results.";
    } elseif (count($results_arr) == 1){
        echo "Found 1 result.";
    } else {
        echo "Nothing found :(";
    }

    // loop the results array and display results
    foreach($results_arr as $result_id) {
        // SQL to get everything from selected id
        $sql = "SELECT id, title, date, body, outcome  FROM experiments WHERE id = :id";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $result_id
        ));
        $final_query = $req->fetch();
        ?>
        <section OnClick="document.location='experiments.php?mode=view&id=<?php echo $final_query['id'];?>'" class="item <?php echo $final_query['outcome'];?>">
        <?php
        // TAGS
        $tagsql = "SELECT tag FROM experiments_tags WHERE item_id = ".$final_query['id'];
        $tagreq = $bdd->prepare($tagsql);
        $tagreq->execute();
        echo "<span class='redo_compact'>".$final_query['date']."</span> ";
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $tagreq->fetch()){
            echo "<a href='experiments.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
        }
        echo "</span>";
        // END TAGS
        echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
        echo "</section>";
    } // end foreach
// /////////////////
// DEFAULT VIEW
// /////////////////
}else{
    $sql = "SELECT * 
        FROM experiments 
        WHERE userid = :userid 
        ORDER BY ".$order." ". $sort." 
        LIMIT ".$limit." 
        OFFSET ".$offset;
    $req = $bdd->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid']);
    $req->execute();

    while ($data = $req->fetch()) {
        if ($display === 'compact') {
                // COMPACT MODE //
                ?>
                <!-- BEGIN CONTENT -->
                <section onClick="document.location='experiments.php?mode=view&id=<?php echo $data['id'];?>'" class='item'>
                <?php
                echo "<span class='".$data['outcome']."_compact'>".$data['date']."</span> ";
                echo stripslashes($data['title']);
                echo "</section>";
        } else {
                ?>
                <!-- BEGIN CONTENT -->
                <section OnClick="document.location='experiments.php?mode=view&id=<?php echo $data['id'];?>'" class="item <?php echo $data['outcome'];?>">
                <?php
                // DATE
                echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' alt='' /> ".$data['date']."</span>";
                // TAGS
                $id = $data['id'];
                $sql = "SELECT tag FROM experiments_tags WHERE item_id = ".$id;
                $tagreq = $bdd->prepare($sql);
                $tagreq->execute();
                echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
                while($tags = $tagreq->fetch()){
                    echo "<a href='experiments.php?q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
                    }
                // END TAGS
                echo    "</span>";
                // TITLE
                echo " <div class='title'>". stripslashes($data['title']) . "</div></section>";
        }

    } // end while
}
// END CONTENT

// PAGINATION
//  only show pagination if there is no search
if(isset($_GET['q']) || isset($_GET['related']) ){
    // no pagination
}else{
    ?>
    <section class='pagination'>
    <?php
    // COUNT TOTAL NUMBER OF ITEMS
        $sql = "SELECT COUNT(id) FROM experiments WHERE userid = ".$_SESSION['userid'];
        $req = $bdd->prepare($sql);
        $req->execute();
        $full = $req->fetchAll();
        $numrows = $full[0][0];

    // find out total pages
    $totalpages = (ceil($numrows / $limit) - 1);
    // if current page is greater than total pages...
    if ($currentpage > $totalpages) {
       // set current page to last page
       $currentpage = $totalpages;
    } // end if
    // if current page is less than first page...
    if ($currentpage < 0) {
       // set current page to first page
       $currentpage = 0;
    } // end if

    /******  build the pagination links ******/
    // range of num links to show
    $range = 3;

    // if not on page 0, show back links
    if ($currentpage != 0) {
       // show << link to go back to page 1
       echo " <a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&currentpage=0'><<</a> ";
       // get previous page num
       $prevpage = $currentpage - 1;
       // show < link to go back to 1 page
       echo " <a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&currentpage=$prevpage'><</a> ";
    } // end if 

    // loop to show links to range of pages around current page
    for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
       // if it's a valid page number...
       if (($x >= 0) && ($x <= $totalpages)) {
          // if we're on current page...
          if ($x == $currentpage) {
             // 'highlight' it but don't make a link
             echo " [<b>$x</b>] ";
          // if not current page...
          } else {
             // make it a link
         echo " <a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&currentpage=$x'>$x</a> ";
          } // end else
       } // end if 
    } // end for
             
    // if not on last page, show forward and last page links	
    if ($currentpage != $totalpages) {
       // get next page
       $nextpage = $currentpage + 1;
        // echo forward link for next page 
       echo " <a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&currentpage=$nextpage'>></a> ";
       // echo forward link for lastpage
       echo " <a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&currentpage=$totalpages'>>></a> ";
    } // end if
    /****** end build pagination links ******/
    ?>
    </section>
<?php
} // end if there is no search
?>
<script>
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=exp'});";
?>
// TOGGLE DIV
$(document).ready(function(){
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('div.toggle_container').slideToggle("slow");
	});
});
</script>
