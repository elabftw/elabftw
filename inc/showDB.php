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
if(isset($_SESSION['prefs']['theme'])) {
require_once("themes/".$_SESSION['prefs']['theme']."/highlight.css");
}
if(isset($_SESSION['prefs']['display'])) {
    $display = $_SESSION['prefs']['display'];
} else {
    $display = 'default';
}
?>
<div id='submenu'>
    <form id='big_search' method='get' action='database.php'>
        <input id='big_search_input' type='search' name='q' size='50' placeholder='Type your search' />
    </form>
<?php // SQL to get items names
$sql = "SELECT * FROM items_types";
$req = $bdd->prepare($sql);
$req->execute();

// 'Create new' dropdown menu
echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/create.gif' alt='create' /> Create new <select onchange=go_url(this.value)><option value=''>--------</option>";
while ($items_types = $req->fetch()) {
    echo "<option value='create_item.php?type=".$items_types['id']."' name='type' ";
    echo ">".$items_types['name']."</option>";
}
echo "</select>";

// 'List all' dropdown menu
// we do the request again to get the list again
$req->execute();
echo "<span class='align_right'><img src='themes/".$_SESSION['prefs']['theme']."/img/search.png' alt='search' /> List all <select onchange=go_url(this.value)><option value=''>--------</option>";
while ($items_types = $req->fetch()) {
    echo "<option value='search.php?type=".$items_types['id']."' name='type' ";
    echo ">".$items_types['name']."</option>";
}
?>
</select></span>
</div>
<!-- end submenu -->

<?php
// SQL for showDB
///////////////
// TAG SEARCH
///////////////
if (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
        $results_arr = array();
        $sql = "SELECT item_id FROM items_tags 
        WHERE tag LIKE :tag";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'tag' => $tag
        ));
        // put resulting ids in the results array
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }

    // show number of results found
    if (count($results_arr) > 1){
        echo "Found ".count($results_arr)." results.";
    } elseif (count($results_arr) == 1){
        echo "Found 1 result.";
    } else {
        echo "No items were found.";
    }

    // clean duplicates
    $results_arr = array_unique($results_arr);
    // loop the results array and display results
    foreach($results_arr as $result_id) {
        showDB($result_id, $display);
    } // end foreach

// NORMAL SEARCH
} elseif (isset($_GET['q']) && !empty($_GET['q'])) {
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    // we make an array for the resulting ids
    $results_arr = array();
    $results_arr = search_item('db', $query, $_SESSION['userid']);
    // filter out duplicate ids and reverse the order; items should be sorted by date
    $results_arr = array_reverse(array_unique($results_arr));
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
        showDB($result_id, $display);
    }
// end if there is a search
} else { // there is no search
    // we show the last 10 uploads
    // get the last id
    $sql = "SELECT * FROM items ORDER BY id DESC LIMIT 10";
    $req = $bdd->prepare($sql);
    $req->execute();
    $count = $req->rowCount();
    if($count == 0) {
        $message = "<strong>Welcome to eLabFTW.</strong> 
            Select an item in the «Create new» list to begin filling your database."; 
        echo display_message('info', $message);
    } else {
        $results_arr = array();
        while($final_query = $req->fetch()) {
            $results_arr[] = $final_query['id'];
        }
        // loop the results array and display results
        echo "<p>Showing last 10 uploads :</p>";
        foreach($results_arr as $result_id) {
            showDB($result_id, $display);
        }
    }
}
?>

<script>
function go_url(x) {
    if(x == '') {
        return;
    }
    location = x;
}
</script>

