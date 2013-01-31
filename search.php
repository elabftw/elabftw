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
// Search.php
require_once('inc/common.php');
$page_title='Search';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<!-- javascript is at the beginning otherwise if there is no input, exit() is called before JS is read -->
<script>
$(document).ready(function(){
    // give focus to search field
    $("#search").focus().select();
    // hide advanced search options
	//$(".toggle_container").hide();
    // toggle advanced search options on click
	$("p.trigger").click(function(){
        $(this).toggleClass("active").next().slideToggle("slow");
	});
});
</script>
<!-- Search page begin -->
<div id='submenu'>
<form id='big_search' name="search" method="post" action="search.php">
<input id="big_search_input" name='find' size='50' type="search" placeholder="Type here" <?php if(isset($_GET['q'])){
    echo "value='".$_GET['q']."'";}?>/>
</form>
<!-- ADVANCED SEARCH -->
<div class='center item'>
<div class='advanced_search_div align_left'>
<form name="search" method="post" action="search.php">

Search in : 
<select>
<option value='experiments' name='type'>Experiments</option>
<?php // SQL to get items names
$sql = "SELECT * FROM items_types";
$req = $bdd->prepare($sql);
$req->execute();
while ($items_types = $req->fetch()) {
    echo "<option value='".$items_types['id']."' name='type'>".$items_types['name']."</option>";
}
?>
</select>
<br />
<select id='first_search_select'>
<option value='title' name='where[]'>Title</option>
<option value='date' name='where[]'>Date</option>
<option value='tags' name='where[]'>Tags</option>
</select>
<input name='what[]' type='text' size='42' />
<br />
<div class='adv_search_div'>
<span class='adv_search_block'>
<select>
<option value='and' name='operator[]'>AND</option>
<option value='or' name='operator[]'>OR</option>
<option value='not' name='operator[]'>NOT</option>
</select>
<select>
<option value='title' name='where[]'>Title</option>
<option value='date' name='where[]'>Date</option>
<option value='tags' name='where[]'>Tags</option>
</select>
<input name='what[]' type='text' size='42' />
<a onClick='add_search_field();'>+</a>
<a class='rm_link' onClick='rm_search_field();'>-</a>
<br />
</span>
</div>
</div>
</form>
</div>
<script>
// get what we want to act on -> second input
    var adv_search_div = $('.adv_search_div').html();
    adv_search_div_nb = 0;

    // add a search block
function add_search_field(){
    $(adv_search_div).appendTo('.adv_search_div');
    adv_search_div_nb++;
    $('.adv_search_block').filter(":last").attr('id', 'block_' + adv_search_div_nb);
}
// remove the last search block
function rm_search_field(){
    $('.adv_search_block').filter(":last").remove();
}
</script>

<?php
// SIMPLE SEARCH
if (isset($_POST['searching_simple']) && ($_POST['searching_simple'] === 'yes')){
    // Is there a search term ?
    if (isset($_POST['find']) && !empty($_POST['find'])) {
        $find = strtoupper(filter_var($_POST['find'], FILTER_SANITIZE_STRING));
    } else { // no input
        echo "<ul class='err'><img src='img/error.png' alt='fail' /> ";
        echo "<li class='inline'>You need to search for something in order to find something...</p>";
        echo "</ul>";
        exit();
    }
    // SIMPLE SEARCH SQL
    $sql = "SELECT * FROM experiments WHERE userid = ".$_SESSION['userid']." AND (title LIKE '%$find%' OR date LIKE '%$find%' OR body LIKE '%$find%')";
    $req = $bdd->prepare($sql);
    $req->execute();
    // This counts the number or results - and if there wasn't any it gives them a little message explaining that 
    $count = $req->rowCount();
    echo "<div id='search_count'>".$count." results for '".stripslashes($find)."' :<br /><br /></div>";
    echo "<div class='search_results_div'>";
    if ($count === 0) {
        echo "<p>Sorry, I couldn't find anything :(</p><br />";
    }
    // Display results
    while ($data = $req->fetch()) {
        showXP($data['id'], $_SESSION['prefs']['display']);
    }
}
// What do we search ?
//if($_POST['search_what'] === 'experiments'){
//    $what = 'experiments';
//    if($_POST['search_where'] === 'title'){
//        $where = 'title';
//        $sql = "SELECT * FROM experiments WHERE userid = :userid AND title LIKE '%$find%'";
//    } elseif ($_POST['search_where'] === 'date'){
//if(filter_var($_POST['find'], FILTER_VALIDATE_INT)) {
//        $where = 'date';
//        $sql = "SELECT * FROM experiments WHERE userid = :userid AND date = $find";
//} else {
//    die('<p>You need to input a date in order to search for a date !</p>');
//}
//    } elseif ($_POST['search_where'] === 'any'){
//        $where = 'any field';
//        $sql = "SELECT * FROM experiments WHERE userid = :userid 
//           AND (title LIKE '%$find%' OR date LIKE '%$find%' OR body LIKE '%$find%')";
//}else{
//    die('<p>What are you doing, Dave ?</p>');
//} //endif what = exp
//
//// if what = protocol
//}elseif($_POST['search_what'] === 'protocols'){
//    $what = 'protocols';
//    if($_POST['search_where'] === 'title'){
//        $where = 'title';
//        $sql = "SELECT * FROM protocols WHERE title LIKE '%$find%'";
//    }elseif($_POST['search_where'] === 'any'){
//        $where = 'any field';
//        $sql = "SELECT * FROM protocols WHERE title LIKE '%$find%'";
//    }else{
//    die('<p>What are you doing, Dave ?</p>');
//    //endif what = protocols
//    }
//}else{
//    die('<p>What are you doing, Dave ?</p>');
//}

// BEGIN RESULTS
//echo "<p>Results for : <em>".stripslashes($find)."</em> in ".$what." ".$where.".</p>";



// FOOTER
require_once('inc/footer.php');
?>
