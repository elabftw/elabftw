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
<h2>PROTOCOLS</h2>
<hr class='flourishes'>
<div id='submenu'><a href="protocols.php?mode=create"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Upload protocol</a> | 
<a href='search.php?for=protocols'><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/search.png' alt='' /> Search</a>
<!-- Quick Search Box (search tags) -->
<form id='quicksearch' method='get' action='protocols.php'>
<input type='search' name='tag' placeholder='Search tag' />
</form><!-- end quick search -->
</div>
<!-- end submenu -->

<?php
// VIEWING PREFS //
$display = $_SESSION['prefs']['display'];
$order = $_SESSION['prefs']['order'];
$sort = $_SESSION['prefs']['sort'];
$limit = $_SESSION['prefs']['limit'];

// Check TAG
if ((isset($_GET['tag'])) && (!empty($_GET['tag']))) {
    $tag = stripslashes(filter_var($_GET['tag'], FILTER_SANITIZE_STRING));
} else {
    $tag = "";
}

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

// SQL for showPR
// reminder : order by and sort must be passed to the prepare(), not during execute()
if(!isset($_GET['tag'])){
$sql = "SELECT * 
    FROM protocols 
    ORDER BY ".$order." ". $sort." 
    LIMIT ".$limit." 
    OFFSET ".$offset;
$req = $bdd->prepare($sql);
$req->execute();

while ($data = $req->fetch()) {
    if ($display === 'compact') {
            // COMPACT MODE //
            ?>
            <!-- BEGIN CONTENT -->
            <section onClick="document.location='protocols.php?mode=view&id=<?php echo $data['id'];?>'" class='item'>
            <?php
            echo stripslashes($data['title']);
            // TAGS
            $sql = "SELECT tag FROM protocols_tags WHERE item_id = ".$data['id'];
            $tagreq = $bdd->prepare($sql);
            $tagreq->execute();
            echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
            while($tags = $tagreq->fetch()){
                echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
                }
            // END TAGS
            echo "</section>";
    } else {
            ?>
            <!-- BEGIN CONTENT -->
            <section OnClick="document.location='protocols.php?mode=view&id=<?php echo $data['id'];?>'" class="item">
            <?php
            // DATE
            echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' alt='' /> ".$data['date']."</span>";
            // TAGS
            $sql = "SELECT tag FROM protocols_tags WHERE item_id = ".$data['id'];
            $tagreq = $bdd->prepare($sql);
            $tagreq->execute();
            echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
            while($tags = $tagreq->fetch()){
                echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
                }
            // END TAGS
            echo    "</span>";
            // TITLE
            echo " <div class='title'>". stripslashes($data['title']) . "</div></section>";
    }
} // end while
// END CONTENT
/**************************************/
}else{// if we search for a tag
    // select all tags like 'tag'
    $sql = "SELECT item_id 
        FROM protocols_tags 
        WHERE tag 
        LIKE '%$tag%' 
        LIMIT ".$limit." 
        OFFSET ".$offset;
    $taglike = $bdd->prepare($sql);
    $taglike->execute();
   // $tagrows = $taglike->rowCount();
    echo "List of protocols with the tag \"".$tag."\" :</p>";
    // Loop on all the protocols with the tag
    while($tags = $taglike->fetch()){
        $sql = "SELECT * 
        FROM protocols 
        WHERE id 
        LIKE '".$tags['item_id']."' 
        ORDER BY ".$order." ". $sort;
        $req = $bdd->prepare($sql);
        $req->execute();
        while($data = $req->fetch()){
                ?>
            <!-- BEGIN CONTENT -->
            <section OnClick="document.location='protocols.php?mode=view&id=<?php echo $data['id'];?>'" class="item">
            <?php
            // DATE
            echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' alt='' /> ".$data['date']."</span>";
            // TAGS
            $id = $data['id'];
            $sql = "SELECT tag FROM protocols_tags WHERE item_id = ".$id;
            $tagreq = $bdd->prepare($sql);
            $tagreq->execute();
            echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
            while($tags = $tagreq->fetch()){
                echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
                }
            // END TAGS
            echo    "</span>";
            // TITLE
            echo "<div class='title'>". stripslashes($data['title']) . "</div></section>";
            // END CONTENT
        } // end while
    }// end while
}// end else
?>

<!-- PAGINATION -->
<section class='pagination'>
<?php
// COUNT TOTAL NUMBER OF ITEMS
if (!isset($_GET['tag']) || empty($_GET['tag'])){
    $sql = "SELECT COUNT(id) FROM protocols WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->prepare($sql);
    $req->execute();
    $full = $req->fetchAll();
    $numrows = $full[0][0];
} else { // if tag filter
    $sql = "SELECT COUNT(id) AS total FROM protocols_tags WHERE tag LIKE '%$tag%' GROUP BY item_id ORDER BY total";
    $req = $bdd->prepare($sql);
    $req->execute();
    $full = $req->fetchAll();
    $numrows = count($full);
}
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
   echo " <a href='{$_SERVER['PHP_SELF']}?currentpage=0'><<</a> ";
   // get previous page num
   $prevpage = $currentpage - 1;
   // show < link to go back to 1 page
   echo " <a href='{$_SERVER['PHP_SELF']}?currentpage=$prevpage'><</a> ";
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
	 echo " <a href='{$_SERVER['PHP_SELF']}?currentpage=$x'>$x</a> ";
      } // end else
   } // end if 
} // end for
		 
// if not on last page, show forward and last page links	
if ($currentpage != $totalpages) {
   // get next page
   $nextpage = $currentpage + 1;
    // echo forward link for next page 
   echo " <a href='{$_SERVER['PHP_SELF']}?currentpage=$nextpage'>></a> ";
   // echo forward link for lastpage
   echo " <a href='{$_SERVER['PHP_SELF']}?currentpage=$totalpages'>>></a> ";
} // end if
/****** end build pagination links ******/
$req->closeCursor();
?>
</section>
<?php
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'protocols.php?mode=create'});
</script>";
?>
