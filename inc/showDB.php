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
    <form id='big_search' method='get' action='database.php'>
        <input type='search' name='q' size='50' placeholder='Type your search' />
    </form>
    <br />
    <a href="create_item.php?type=pro"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Add a protocol</a> 
    <a href="create_item.php?type=pla"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Add a plasmid</a>
    <a href="create_item.php?type=ant"><img src="themes/<?php echo $_SESSION['prefs']['theme'];?>/img/create.gif" alt="" /> Add an antibody</a>
<div id='export_menu'>
    <a href='make_csv.php'><img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export in spreadsheet file' /></a>
</div>
</div>
<!-- end submenu -->

<?php
// SQL for showDB
// we show the last 10 uploads
if(!isset($_GET['q']) || empty($_GET['q'])){ // if there is no search
    echo "<p>Showing last 10 uploads :</p>";
    $sql = "SELECT * 
        FROM items 
        ORDER BY id DESC 
        LIMIT 10";
    $req = $bdd->prepare($sql);
    $req->execute();
    while ($data = $req->fetch()) {
        ?>
            <section OnClick="document.location='database.php?mode=view&id=<?php echo $data['id'];?>'" class="item <?php echo $data['type'];?>">
<?php
        // TAGS
        $sql = "SELECT tag FROM items_tags WHERE item_id = ".$data['id'];
        $tagreq = $bdd->prepare($sql);
        $tagreq->execute();
        echo "<span class='redo_compact'>".$data['date']."</span> ";
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $tagreq->fetch()){
            echo "<a href='database.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
        }
        echo "</span>";
        // END TAGS
        ?>
        <?php
        echo "<p class='title'>". stripslashes($data['title']) . "</p>";
        echo "</section>";
    } // end while


} else { //there is a SEARCH
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    // we make an array for the resulting ids
    $results_arr = array();
    // search in title date and body
    $sql = "SELECT id FROM items 
        WHERE (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%') LIMIT 100";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $_SESSION['userid']
    ));
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
    $sql = "SELECT item_id FROM uploads WHERE (comment LIKE '%$query%' OR real_name LIKE '%$query%') LIMIT 100";
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
        $sql = "SELECT id, title, date, body, type FROM items WHERE id = :id";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'id' => $result_id
        ));
        $final_query = $req->fetch();
        ?>
        <section OnClick="document.location='database.php?mode=view&id=<?php echo $final_query['id'];?>'" class="item <?php echo $final_query['type'];?>">
        <?php
        // TAGS
        $tagsql = "SELECT tag FROM items_tags WHERE item_id = ".$final_query['id'];
        $tagreq = $bdd->prepare($tagsql);
        $tagreq->execute();
        echo "<span class='redo_compact'>".$final_query['date']."</span> ";
        echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
        while($tags = $tagreq->fetch()){
            echo "<a href='database.php?mode=show&q=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
        }
        echo "</span>";
        // END TAGS
        echo "<p class='title'>". stripslashes($final_query['title']) . "</p>";
        echo "</section>";
    } // end foreach
} // end if there is a search

// KEYBOARD SHORTCUTS
echo "<script>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=prot'});
</script>";
?>
