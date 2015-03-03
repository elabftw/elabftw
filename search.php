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
require_once 'inc/common.php';
require_once 'inc/locale.php';
$page_title = _('Search');
$selected_menu = 'Search';
require_once 'inc/head.php';
require_once 'inc/info_box.php';
?>

<!-- Advanced Search page begin -->
<menu class='border'><a href='experiments.php?mode=show'><img src='img/arrow-left-blue.png' class='bot5px' alt='' /> <?php echo _('Back to experiments listing'); ?></a></menu>
<section class='searchform box'>
    <form name="search" method="get" action="search.php">

        <div class='row'>
            <!-- _('Search') IN-->
            <div class='col-md-4'>
                <label for='searchin'><?php echo _('Search in'); ?></label>
                <select name='type' id='searchin'>
                    <option value='experiments'><?php echo ngettext('Experiment', 'Experiments', 2); ?></option>
                    <?php // Database items types
                    $sql = "SELECT * FROM items_types WHERE team = :team";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'team' => $_SESSION['team_id']
                    ));
                    while ($items_types = $req->fetch()) {
                        echo "<option value='" . $items_types['id'] . "'";
                        // item get selected if it is in the search url
                        if (isset($_GET['type']) && ($items_types['id'] == $_GET['type'])) {
                            echo " selected='selected'";
                        }
                        echo ">" . $items_types['name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <!-- END SEARCH IN -->

            <!-- SEARCH ONLY -->
            <div class='col-md-8'>
                <label for'searchonly'><?php echo _('Search only in experiments owned by:'); ?> </label><br>
                <select id='searchonly' name='owner'>
                    <option value=''><?php echo _('Yourself'); ?></option>
                    <?php
                    $users_sql = "SELECT userid, firstname, lastname FROM users WHERE team = :team";
                    $users_req = $pdo->prepare($users_sql);
                    $users_req->execute(array(
                        'team' => $_SESSION['team_id']
                    ));
                    while ($users = $users_req->fetch()) {
                        echo "<option value='" . $users['userid'] . "'";
                            // item get selected if it is in the search url
                            if (isset($_GET['owner']) && ($users['userid'] == $_GET['owner'])) {
                                echo " selected='selected'";
                            }
                            echo ">" . $users['firstname'] . " " . $users['lastname'] . "</option>";
                    }
                    ?>
                </select><br>
                <!-- search everyone box -->
                <label for='all_experiments_chkbx'>(<?php echo _("search in everyone's experiments"); ?> </label>
                <input name="all" id='all_experiments_chkbx' value="y" type="checkbox" <?php
                    // keep the box checked if it was checked
                    if (isset($_GET['all'])) {
                        echo "checked=checked";
                    }?>>)
            </div>
            <!-- END _('Search') ONLY -->
        </div>

        <div class='row'>
            <!-- _('Search') DATE -->
            <div class='col-md-8'>
                <label for='from'><?php echo _('Where date is between'); ?></label>
                <input id='from' name='from' type='text' size='8' class='datepicker' value='<?php
                if (isset($_GET['from']) && !empty($_GET['from'])) {
                    echo check_date($_GET['from']);
                }
                ?>'/>
                <label span style='margin:0 10px;' for='to'> <?php echo _('and'); ?> </label>
                <input id='to' name='to' type='text' size='8' class='datepicker' value='<?php
                    if (isset($_GET['to']) && !empty($_GET['to'])) {
                        echo check_date($_GET['to']);
                    }
                ?>'/>
            </div>
            <!-- END SEARCH DATE -->
        </div>

        <div class='row'>
            <!-- TITLE -->
            <div class='col-md-6'>
            <label for='title'><?php echo _('And title contains'); ?></label>
            <input id='title' name='title' type='text' value='<?php
                if (isset($_GET['title']) && !empty($_GET['title'])) {
                    echo check_title($_GET['title']);
                }
                ?>'/>
            </div>
            <!-- STATUS -->
            <div class='col-md-4'>
                <label class='block' for='status'><?php echo _('And status is'); ?></label>
                <select id='status' name="status">
                    <option value='' name='status'><?php echo _('select status'); ?></option>
                    <?php
                    // put all available status in array
                    $status_arr = array();
                    // SQL TO GET ALL STATUS INFO
                    $sql = "SELECT id, name, color FROM status WHERE team = :team_id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'team_id' => $_SESSION['team_id']
                    ));

                    while ($status = $req->fetch()) {
                        $status_arr[$status['id']] = $status['name'];
                    }
                    ?>
                        <?php
                        // now display all possible values of status in select menu
                        foreach ($status_arr as $key => $value) {
                            echo "<option ";
                            if (isset($_GET['status']) && $_GET['status'] == $key) {
                                echo "selected ";
                            }
                            echo "value='$key'>$value</option>";
                        }
                        ?>
                    </select>
            </div>

        </div>

        <div class='row'>
            <div class='col-md-6'>
            <label for='body'><?php echo _('And body contains'); ?></label>
            <input id='body' name='body' type='text' value='<?php
                if (isset($_GET['body']) && !empty($_GET['body'])) {
                    echo check_body($_GET['body']);
                }
                ?>'/>
            </div>
            <!-- END TITLE -->

            <!-- RATING -->
            <div class='col-md-4'>
                <label class='block' for='rating'><?php echo _('And rating is'); ?></label>
                <select id='rating' name='rating'>
                    <option value='' name='rating'><?php echo _('select number of stars'); ?></option>
                    <option value='no' name='rating'><?php echo _('Unrated'); ?></option>
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                    echo "<option value='" . $i . "' name='rating'";
                        // item get selected if it is in the search url
                    if (isset($_GET['rating']) && ($_GET['rating'] == $i)) {
                        echo " selected='selected'";
                    }
                    echo ">" . $i . "</option>";
                    }?>
                </select>
            </div>
            <!-- END RATING -->
        </div>

        <div style='margin:30px;' class='center'>
            <button id='searchButton' class='button' value='Submit' type='submit'><?php echo _('Launch search'); ?></button>
        </div>
    </form>
</section>


<?php
/*
 * Here the search begins
 */

// If there is a search, there will be get parameters, so this is our main switch
if (isset($_GET)) {
    // assign variables from get
    if (isset($_GET['title']) && !empty($_GET['title'])) {
        if(strrpos(trim($_GET['title']), " ") !== false){
            $expTitle = explode(' ',trim($_GET['title']));
            $title = '';
        }else{ 
            $title = filter_var(trim($_GET['title']), FILTER_SANITIZE_STRING); 
        }
    } else {
        $title = '';
    }
    if (isset($_GET['body']) && !empty($_GET['body'])) {
        if(strrpos(trim($_GET['body']), " ") !== false){
            $expBody = explode(' ',trim($_GET['body']));
            $body = '';
        }else{ 
            $body = filter_var(check_body(trim($_GET['body'])), FILTER_SANITIZE_STRING); 
        }
    } else {
        $body = '';
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
    if (isset($_GET['status']) && !empty($_GET['status']) && is_pos_int($_GET['status'])) {
        $status = $_GET['status'];
    } else {
        $status = '';
    }
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
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
        $owner = '';
    }
    

    // PREPARE SQL query
    // Title search
    if (!empty($title)) {
        $sqlTitle = " AND title LIKE '%$title%'";
    } else if (isset($expTitle)) {
        $sqlTitle = " AND (";
        foreach ($expTitle as $key => $value) {
            if($key != 0) { 
                $sqlTitle .= " OR "; 
            }
            $sqlTitle .= "title LIKE '%$value%'";
        }
        $sqlTitle .= ")";
    } else { 
        $sqlTitle = ""; 
    }

    // Body search
    if (!empty($body)) {
        $sqlBody = " AND body LIKE '%$title%'";
    } else if (isset($expBody)) {
        $sqlBody = " AND (";
        foreach ($expBody as $key => $value) {
            if($key != 0) {
                $sqlBody .= " OR ";
            }
            $sqlBody .= "body LIKE '%$value%'";
        }
        $sqlBody .= ")";
    } else { 
        $sqlBody = ""; 
    }

    // Status search
    if (!empty($status)) {
        $sqlStatus = " AND status LIKE '$status'";
    } else { 
        $sqlStatus = ""; 
    }

    // Rating search
    if (!empty($rating)) {
        $sqlRating = " AND rating LIKE '$rating'";
    }else { $sqlRating = ""; }

    // Date search
    if (!empty($from) && !empty($to)) {
        $sqlDate = " AND date BETWEEN '$from' AND '$to'";
    }else if (!empty($from) && empty($to)) {
        $sqlDate = " AND date BETWEEN '$from' AND '99991212'";
    }else if (empty($from) && !empty($to)) {
        $sqlDate = " AND date BETWEEN '00000101' AND '$to'";
    }else { 
        $sqlDate = ""; 
    }


    // EXPERIMENT SEARCH
    if (isset($_GET['type'])) {
        if ($_GET['type'] === 'experiments') {

            if (isset($_GET['all']) && !empty($_GET['all'])) { 
                $sqlFirst = " team = " . $_SESSION['team_id'];
            }else{ 
                $sqlFirst = " userid = :userid"; 
            }

            $sql = "SELECT * FROM experiments WHERE" . $sqlFirst . $sqlTitle .  $sqlBody . $sqlStatus . $sqlDate ;
            $req = $pdo->prepare($sql);
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
                foreach ($results_id as $id) {
                    $results_id_str .= $id . "+";
                }
                // remove last +
                $results_id_str = rtrim($results_id_str, '+');
    ?>

                <div class='align_right'><a name='anchor'></a>
                <p class='inline'><?php echo _('Export this result:'); ?> </p>
                <a href='make_zip.php?id=<?php echo $results_id_str; ?>&type=experiments'>
                <img src='img/zip.png' title='make a zip archive' alt='zip' /></a>

                    <a href='make_csv.php?id=<?php echo $results_id_str; ?>&type=experiments'><img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export in spreadsheet file' /></a>
                </div>
    <?php
                echo "<p id='search_count'>" . $count . " " . ngettext("result found", "results found", $count) . "</p>";
                // Display results
                echo "<hr>";
                foreach ($results_id as $id) {
                    showXP($id, $_SESSION['prefs']['display']);
                }
            } else { // no results
                display_message('error_nocross', _("Sorry. I couldn't find anything :("));
            }

    // DATABASE SEARCH
    } elseif (is_pos_int($_GET['type'])) {

        $sqlFirst = " type = :type";
        $sql = "SELECT * FROM items WHERE" . $sqlFirst . $sqlTitle .  $sqlBody . $sqlRating . $sqlDate ;
        $req = $pdo->prepare($sql);
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
            foreach ($results_id as $id) {
                $results_id_str .= $id . "+";
            }
            // remove last +
            $results_id_str = rtrim($results_id_str, '+');
?>

            <div class='align_right'><a name='anchor'></a>
            <p class='inline'><?php echo _('Export this result:'); ?> </p>
            <a href='make_zip.php?id=<?php echo $results_id_str; ?>&type=items'>
            <img src='img/zip.png' title='make a zip archive' alt='zip' /></a>

                <a href='make_csv.php?id=<?php echo $results_id_str; ?>&type=items'><img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export in spreadsheet file' /></a>
            </div>
<?php
            if ($count == 1) {
            echo "<div id='search_count'>" . $count . " result</div>";
            } else {
            echo "<div id='search_count'>" . $count . " results</div>";
            }
            // Display results
            echo "<hr>";
            foreach ($results_id as $id) {
                showDB($id, $_SESSION['prefs']['display']);
            }
        } else { // no results
            display_message('error_nocross', _("Sorry. I couldn't find anything :("));
        }
    }
    }
}
?>

<script>
$(document).ready(function(){
    // DATEPICKER
    $( ".datepicker" ).datepicker({dateFormat: 'yymmdd'});
<?php
// scroll to anchor if there is a search
if (isset($_GET)) {
    echo "location.hash = '#anchor';";
}?>
});
</script>

<?php
require_once('inc/footer.php');
