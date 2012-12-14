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
if(!isset($_GET['q']) || empty($_GET['q'])){ // if there is no search
    // we show the last 10 uploads
    // get the last id
    $sql = "SELECT * FROM items ORDER BY id DESC LIMIT 10";
    $req = $bdd->prepare($sql);
    $req->execute();
    $results_arr = array();
    while($final_query = $req->fetch()) {
        $results_arr[] = $final_query['id'];
    }
    // loop the results array and display results
    echo "<p>Showing last 10 uploads :</p>";
    foreach($results_arr as $result_id) {
        showDB($result_id, $display);
    }

} else { //there is a SEARCH
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
} // end if there is a search

// KEYBOARD SHORTCUTS
echo "<script>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=prot'});
</script>";
?>
