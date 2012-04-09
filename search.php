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
$page_title='SEARCH';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<!-- javascript is at the beginning otherwise if there is no input, exit() is called before JS is read -->
<script type="text/javascript">
$(document).ready(function(){
    // give focus to search field
    $("#search").focus().select();
    // hide advanced search options
	$(".toggle_container").hide();
    // toggle advanced search options on click
	$("p.trigger").click(function(){
        $(this).toggleClass("active").next().slideToggle("slow");
	});
});

</script>
<!-- Search page begin -->
<section class='item'>
<div class='center'>
<form name="search" method="post" action="search.php">
<input id="search" name='find' size='63' type="text" placeholder="Type here" <?php if(isset($_POST['searching_simple'])){
    echo "value='".$_POST['find']."'";}?>/>
<br />
<input id='submit' type='submit' value='Search' />
<a id='submitlucky' href='#' onClick='lucky()'>I'm Feeling Lucky</a>
<input type='hidden' name='searching_simple' value='yes' />
</form>
<script type='text/javascript'>
function lucky() {
    // get input text
    var search = $('#search').val();
    // pass it to lucky.php; open tab
    window.open('lucky.php?find=' + search, '_blank');
    }
    </script>
<!-- ADVANCED SEARCH
<p class='trigger'>↓ Advanced search ↓</p>
<div class='toggle_container align_left'>
<form name="search" method="post" action="search.php">

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
</section>
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
-->
</section>

<?php
// SIMPLE SEARCH
if((isset($_POST['searching_simple'])) && ($_POST['searching_simple'] === 'yes')){
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
if ($count === 0){
    echo "<p>Sorry, I couldn't find anything :(</p><br />";
}
// Display results
while ($data = $req->fetch()) {
        $outcome = $data['outcome'];
?>
<section OnClick="window.open('experiments.php?mode=view&id=<?php echo $data['id'];?>', '_blank');" class="search_result">
<?php
// DATE
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' alt='' /> ".$data['date']."</span>";
// TITLE
echo "<div class=''>". stripslashes($data['title']) . "</div></section>";
} // end while
// END CONTENT
} // end if searching_simple
echo "</div>";
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
