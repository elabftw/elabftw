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
require_once('inc/common.php');
$page_title='Search';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<!-- Search page begin -->
<h2>SEARCH</h2>
<section class='item'>
<div class='center'>
<form id='search_box' name="search" method="post" action="search.php">
<input type="hidden" name="searching" value="yes" />
<span>Find </span>
<span> in </span>
<select name="search_what">
<option value="experiments">Experiments</option>
<option <?php if(isset($_POST['for'])){echo ($_POST['for'] === 'protocols') ? "selected" : "";}?> value="protocols">Protocols</option><br />
</select><br />
<span>where </span>
<select name="search_where">
<option value="any">anything</option>
<option value="title">title</option>
<option value="date">date</option>
</select><br />
<span>contains </span>
<input type="text" name="find" />
<br />
<span>sort results by :</span>
<select name="order">
<option <?php
echo (isset($_POST['order']) === 'id') ? 'selected' : '';?>value="id">Experiment n°</option>
<option <?php
    if((isset($_POST['order']))&&($_POST['order'] === 'title')){
        echo ' selected ';
    } ?>value="title">Title</option>
<option <?php
    if((isset($_POST['order']))&&($_POST['order'] === 'date')){
        echo ' selected ';
    }?>value="date">Date</option>
</select>
      <select name="sort">
      <option value="desc">DESC</option>
<option <?php
        if((isset($_POST['sort']))&&($_POST['sort'] === 'asc')){
            echo ' selected ';
        }?>value="asc">ASC</option>
</select><br />
<span>limit to </span><input value='<?php 
if((isset($_POST['limit']))
    &&(!empty($_POST['limit']))
    &&(filter_var($_POST['limit'], FILTER_VALIDATE_INT))){
        echo $_POST['limit'];
    }else{
        echo '15';}
?>' name='limit' size='2' maxlength='2'><span> results by page</span><br />
<input type="submit" id='submit' name="Submit" value="Search" onclick="this.form.submit();" />
</form>
</div>
</section>

<?php
////////// SEARCH
// If there is a search
if((isset($_POST['searching'])) && ($_POST['searching'] === 'yes')){

// Is there a search term ?
if (isset($_POST['find']) && !empty($_POST['find'])) {
    $find = filter_var($_POST['find'], FILTER_SANITIZE_STRING);
} else {
    echo "<ul class='err'><img src='img/error.png' alt='fail' /> ";
    echo "<li class='inline'>You need to search for something in order to find something...</p>";
    echo "</ul>";
    exit();
}

//We preform a bit of filtering
$find = strtoupper($find);
$find = strip_tags($find);
$find = trim($find);

// What do we search ?
if($_POST['search_what'] === 'experiments'){
    $what = 'experiments';
    if($_POST['search_where'] === 'title'){
        $where = 'title';
        $sql = "SELECT * FROM experiments WHERE userid = :userid AND title LIKE '%$find%'";
    } elseif ($_POST['search_where'] === 'date'){
if(filter_var($_POST['search_what'], FILTER_VALIDATE_INT)) {
        $where = 'date';
        $sql = "SELECT * FROM experiments WHERE userid = :userid AND date = $find";
} else {
    die('<p>You need to input a date in order to search for a date !</p>');
}
    } elseif ($_POST['search_where'] === 'any'){
        $where = 'any field';
        $sql = "SELECT * FROM experiments WHERE userid = :userid 
           AND (title LIKE '%$find%' OR date LIKE '%$find%' OR body LIKE '%$find%')";
}else{
    die('<p>What are you doing, Dave ?</p>');
} //endif what = exp

// if what = protocol
}elseif($_POST['search_what'] === 'protocols'){
    $what = 'protocols';
    if($_POST['search_where'] === 'title'){
        $where = 'title';
        $sql = "SELECT * FROM protocols WHERE title LIKE '%$find%'";
    }elseif($_POST['search_where'] === 'any'){
        $where = 'any field';
        $sql = "SELECT * FROM protocols WHERE title LIKE '%$find%'";
    }else{
    die('<p>What are you doing, Dave ?</p>');
    //endif what = protocols
    }
}else{
    die('<p>What are you doing, Dave ?</p>');
}

// BEGIN RESULTS
echo "<p>Results for : <em>".stripslashes($find)."</em> in ".$what." ".$where.".</p>";

// SQL for search
$req = $bdd->prepare($sql);
if($what === 'experiments'){$req->bindParam(':userid', $_SESSION['userid']);}
$req->execute();
// Display results
while ($data = $req->fetch()) {
    if($what === 'experiments'){
        $outcome = $data['outcome'];
    }else{
        $outcome = 'item';
    }
?>
<section OnClick="document.location='<?php echo $what;?>.php?mode=view&id=<?php echo $data['id'];?>'" class="<?php echo $outcome;?>">
<?php
// DATE
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' alt='' /> ".$data['date']."</span>";
// TAGS
$id = $data['id'];
$sql = "SELECT tag FROM experiments_tags WHERE item_id = ".$id;
$tagreq = $bdd->prepare($sql);
$tagreq->execute();
echo "<span class='tags'><img src='img/tags.gif' alt='' /> ";
while($tags = $tagreq->fetch()){
    echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
    }
// END TAGS
echo    "</span>";
// TITLE
echo "<div class='title'>". stripslashes($data['title']) . "</div></section>";
} // end while
// END CONTENT

// This counts the number or results - and if there wasn't any it gives them a little message explaining that 
$count = $req->rowCount();
if ($count === 0){
    echo "<p>Sorry, I couldn't find anything :(</p>";
}
$req->closeCursor();
}

// FOOTER
require_once('inc/footer.php');
