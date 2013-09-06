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
<!-- Advanced Search page begin -->
<div class='item'>
    <div class='align_left'>
        <form name="search" method="get" action="search.php">
            <!-- SUBMIT BUTTON -->
            <button class='submit_search_button' type='submit'>
                <img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' />
                <p>FIND</p>
            </button>
            <p class='inline'>Search in : </p>
            <select name='type'>
                <option value='experiments'>Experiments</option>
                <?php // Database items types
                $sql = "SELECT * FROM items_types";
                $req = $bdd->prepare($sql);
                $req->execute();
                while ($items_types = $req->fetch()) {
                    echo "<option value='".$items_types['id']."'";
                    // item get selected if it is in the search url
                    if(isset($_GET['type']) && ($items_types['id'] == $_GET['type'])) {
                        echo " selected='selected'";
                    } 
                    echo ">".$items_types['name']."</option>";
                }
                ?>
            </select>
           <!-- search everyone box --> 
            <label for='all_experiments_chkbx'>(search in everyone's experiment </label>
            <input name="all" id='all_experiments_chkbx' value="y" type="checkbox" <?php
                // keep the box checked if it was checked
                if(isset($_GET['all'])){
                    echo "checked=checked";
                }?>>)
            <br />
            <br />
            Search only in experiments owned by : <select name='owner'>
            <option value=''>Select a member</option>
            <?php
            $users_sql = "SELECT userid, firstname, lastname FROM users";
            $users_req = $bdd->prepare($users_sql);
            $users_req->execute();
            while ($users = $users_req->fetch()) {
                echo "<option value='".$users['userid']."'";
                    // item get selected if it is in the search url
                    if(isset($_GET['owner']) && ($users['userid'] == $_GET['owner'])) {
                        echo " selected='selected'";
                    } 
                    echo ">".$users['firstname']." ".$users['lastname']."</option>";
                }
                ?>
            </select>
            <br />
            <br />
            <div id='search_inputs_div'>
            <p class='inline'>Where date is between :</p><input name='from' type='text' size='6' class='search_inputs datepicker' value='<?php
                if(isset($_GET['from']) && !empty($_GET['from'])) {
                    echo check_date($_GET['from']);
                }
?>'/><br />
<br />
            <p class='inline'>and :</p><input name='to' type='text' size='6' class='search_inputs datepicker' value='<?php
                if(isset($_GET['to']) && !empty($_GET['to'])) {
                    echo check_date($_GET['to']);
                }
?>'/><br />
<br />
<p class='inline'>And title contains </p><input name='title' type='text' class='search_inputs' value='<?php
                if(isset($_GET['title']) && !empty($_GET['title'])) {
                    echo check_title($_GET['title']);
                }
?>'/><br />
<br />
<!--
                <p class='inline'>Tags</p><input name='tags' type='text' class='search_inputs'/><br />
<br />
-->
<p class='inline'>And body contains</p><input name='body' type='text' class='search_inputs' value='<?php
                if(isset($_GET['body']) && !empty($_GET['body'])) {
                    echo check_body($_GET['body']);
                }
?>'/><br />
<br />
                <p class='inline'>And status is </p><select name='status' class='search_inputs'>
<option value='' name='status'>select status</option>
<option value='running' name='status'<?php
                    if(isset($_GET['status']) && ($_GET['status'] == 'running')) {
                        echo " selected='selected'";
                    }
?>
>Running</option>
<option value='success' name='status'<?php
                    if(isset($_GET['status']) && ($_GET['status'] == 'success')) {
                        echo " selected='selected'";
                    }
?>
>Success</option>
<option value='redo' name='status'<?php
                    if(isset($_GET['status']) && ($_GET['status'] == 'redo')) {
                        echo " selected='selected'";
                    }
?>
>Redo</option>
<option value='fail' name='status'<?php
                    if(isset($_GET['status']) && ($_GET['status'] == 'fail')) {
                        echo " selected='selected'";
                    }
?>
>Fail</option>
</select>
<br />
<br /> <p class='inline'>And rating is </p><select name='rating' class='search_inputs'>
<option value='' name='rating'>select number of stars</option>
<option value='no' name='rating'>Unrated</option>
<?php
for($i=1; $i<=5; $i++) {
    echo "<option value='".$i."' name='rating'";
        // item get selected if it is in the search url
    if(isset($_GET['rating']) && ($_GET['rating'] == $i)) {
        echo " selected='selected'";
    }
    echo ">".$i."</option>";
}
?>
</select>
<br />
            </div>

            </div>
            </div>

        </form>
    </div>
</div>


<?php
/*
 * Here the search begins
 */

// If there is a search, there will be get parameters, so this is our main switch
if (isset($_GET)) {
    // assign variables from get
    if (isset($_GET['title']) && !empty($_GET['title'])) {
        $title =  filter_var($_GET['title'], FILTER_SANITIZE_STRING);
    } else {
        $title = '';
    }
    if (isset($_GET['from']) && !empty($_GET['from'])) {
        $from = check_date($_GET['from']);
    } else {
        $from = '';
    }
    if (isset($_GET['to']) && !empty($_GET['to'])) {
        $to = check_date($_GET['to']);
    } else {
        $to = '';
    }
    if (isset($_GET['tags']) && !empty($_GET['tags'])) {
        $tags = filter_var($_GET['tags'], FILTER_SANITIZE_STRING);
    } else {
        $tags = '';
    }
    if (isset($_GET['body']) && !empty($_GET['body'])) {
        $body = check_body($_GET['body']);
    } else {
        $body = '';
    }
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = check_status($_GET['status']);
    } else {
        $status = '';
    }
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if($_GET['rating'] === 'no') {
            $rating = '0';
        } else {
        $rating = intval($_GET['rating']);
        }
    } else {
        $rating = '';
    }
    if (isset($_GET['owner']) && !empty($_GET['owner']) && is_pos_int($_GET['owner'])) {
        $owner_search = true;
        $owner = $_GET['owner'];
    } else {
        $owner_search = false;
    }

    // EXPERIMENT ADVANCED SEARCH
    if(isset($_GET['type'])) {
        if($_GET['type'] === 'experiments') {
            // SQL
            // the BETWEEN stuff makes the date mandatory, so we switch the $sql with/without date
            if(isset($_GET['to']) && !empty($_GET['to'])) {

                if(isset($_GET['all']) && !empty($_GET['all'])) {
            $sql = "SELECT * FROM experiments WHERE title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%' AND date BETWEEN '$from' AND '$to'";
                } else { //search only in your experiments
            $sql = "SELECT * FROM experiments WHERE userid = :userid AND title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%' AND date BETWEEN '$from' AND '$to'";
                }


            } elseif(isset($_GET['from']) && !empty($_GET['from'])) {
                if(isset($_GET['all']) && !empty($_GET['all'])) {
            $sql = "SELECT * FROM experiments WHERE title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%' AND date BETWEEN '$from' AND '991212'";
                } else { //search only in your experiments
            $sql = "SELECT * FROM experiments WHERE userid = :userid AND title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%' AND date BETWEEN '$from' AND '991212'";
                }


            } else { // no date input
                if(isset($_GET['all']) && !empty($_GET['all'])) {
            $sql = "SELECT * FROM experiments WHERE title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%'";
                } else { //search only in your experiments
            $sql = "SELECT * FROM experiments WHERE userid = :userid AND title LIKE '%$title%' AND body LIKE '%$body%' AND status LIKE '%$status%'";
                }


            }

            $req = $bdd->prepare($sql);
            // if there is a selection on 'owned by', we use the owner id as parameter
            if ($owner_search) {
                $req->execute(array(
                    'userid' => $owner
                ));
            } else {
            $req->execute(array(
                'userid' => $_SESSION['userid']
            ));
            }
            // This counts the number or results - and if there wasn't any it gives them a little message explaining that 
            $count = $req->rowCount();
            if ($count > 0) {
                // make array of results id
                $results_id = array();
                while ($get_id = $req->fetch()) {
                    $results_id[] = $get_id['id'];
                }
                // sort by id, biggest (newer item) comes first
                $results_id = array_reverse($results_id);
                
                // construct string for links to export results
                $results_id_str = "";
                foreach($results_id as $id) {
                    $results_id_str .= $id."+";
                }
                // remove last +
                $results_id_str = substr($results_id_str, 0, -1);
    ?>

                <div id='export_menu'>
                <p class='inline'>Export this result : </p>
                <a href='make_zip.php?id=<?php echo $results_id_str;?>&type=exp'>
                <img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/zip.gif' title='make a zip archive' alt='zip' /></a>

                    <a href='make_csv.php?id=<?php echo $results_id_str;?>&type=exp'><img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export in spreadsheet file' /></a>
                </div>
    <?php
                if ($count == 1) {
                echo "<div id='search_count'>".$count." result</div>";
                } else {
                echo "<div id='search_count'>".$count." results</div>";
                }
                echo "<div class='search_results_div'>";
                // Display results
                echo "<hr>";
                foreach ($results_id as $id) {
                    showXP($id, $_SESSION['prefs']['display']);
                }
            } else { // no results
                $message = "Sorry, I couldn't find anything :(";
                echo display_message('error', $message);
            }

    // DATABASE ADVANCED SEARCH
    } elseif (is_pos_int($_GET['type'])) {
            // SQL
            // the BETWEEN stuff makes the date mandatory, so we switch the $sql with/without date
            if(isset($_GET['to']) && !empty($_GET['to'])) {
            $sql = "SELECT * FROM items WHERE type = :type AND title LIKE '%$title%' AND body LIKE '%$body%' AND rating LIKE '%$rating%' AND date BETWEEN '$from' AND '$to'";
            } elseif(isset($_GET['from']) && !empty($_GET['from'])) {
            $sql = "SELECT * FROM items WHERE type = :type AND title LIKE '%$title%' AND body LIKE '%$body%' AND rating LIKE '%$rating%' AND date BETWEEN '$from' AND '991212'";
            } else { // no date input
            $sql = "SELECT * FROM items WHERE type = :type AND title LIKE '%$title%' AND body LIKE '%$body%' AND rating LIKE '%$rating%'";
            }

        $req = $bdd->prepare($sql);
        $req->execute(array(
            'type' => $_GET['type']
        ));
        $count = $req->rowCount();
        if ($count > 0) {
            // make array of results id
            $results_id = array();
            while ($get_id = $req->fetch()) {
                $results_id[] = $get_id['id'];
            }
            // sort by id, biggest (newer item) comes first
            $results_id = array_reverse($results_id);
            
            // construct string for links to export results
            $results_id_str = "";
            foreach($results_id as $id) {
                $results_id_str .= $id."+";
            }
            // remove last +
            $results_id_str = substr($results_id_str, 0, -1);
?>

            <div id='export_menu'>
            <p class='inline'>Export this result : </p>
            <a href='make_zip.php?id=<?php echo $results_id_str;?>&type=items'>
            <img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/zip.gif' title='make a zip archive' alt='zip' /></a>

                <a href='make_csv.php?id=<?php echo $results_id_str;?>&type=items'><img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export in spreadsheet file' /></a>
            </div>
<?php
            if ($count == 1) {
            echo "<div id='search_count'>".$count." result</div>";
            } else {
            echo "<div id='search_count'>".$count." results</div>";
            }
            echo "<div class='search_results_div'>";
            // Display results
            echo "<hr>";
            foreach ($results_id as $id) {
                showDB($id, $_SESSION['prefs']['display']);
            }
        } else { // no results
            $message = "Sorry, I couldn't find anything :(";
            echo display_message('error', $message);
        }
    }
    }
}
?>

<script>
$(document).ready(function(){
    // DATEPICKER
    $( ".datepicker" ).datepicker({dateFormat: 'ymmdd'});
});
</script>

<?php require_once('inc/footer.php');?>

