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
            <!-- SEARCH IN-->
            <?php
            if (isset($_GET['type']) && $_GET['type'] == 'database') {
                $seldb = " selected='selected'";
            } else {
                $seldb = "";
            }
            ?>
            <div class='col-md-3'>
                <label for='searchin'><?php echo _('Search in'); ?></label>
                <select name='type' id='searchin'>
                    <option value='experiments'><?php echo ngettext('Experiment', 'Experiments', 2); ?></option>
                    <option disabled>----------------</option>
                    <option value='database' <?php echo $seldb; ?>><?php echo _('Database'); ?></option>
                    <?php // Database items types
                    $sql = "SELECT * FROM items_types WHERE team = :team ORDER BY name ASC";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'team' => $_SESSION['team_id']
                    ));
                    while ($items_types = $req->fetch()) {
                        echo "<option value='" . $items_types['id'] . "'";
                        // item get selected if it is in the search url
                        if (isset($_GET['type']) && $items_types['id'] == $_GET['type']) {
                            echo " selected='selected'";
                        }
                        echo "> - " . $items_types['name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <!-- END SEARCH IN -->
            <!-- SEARCH WITH TAG -->
            <div class='col-md-3' id='tag_exp'>
                <label for='tag_exp'><?php echo _('With the tag'); ?></label>
                <select name='tag_exp'>
                    <option value=''><?php echo _('Select a Tag'); ?></option>
                    <?php // Database items types
                    // TODO https://github.com/elabftw/elabftw/issues/135
                    $sql = "SELECT tag, COUNT(id) as nbtag, userid FROM experiments_tags WHERE userid = :userid GROUP BY tag ORDER BY tag ASC";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'userid' => $_SESSION['userid']
                    ));
                    while ($exp_tags = $req->fetch()) {
                        echo "<option value='" . $exp_tags['tag'] . "'";
                        // item get selected if it is in the search url
                        if (isset($_GET['tag_exp']) && ($exp_tags['tag'] == $_GET['tag_exp'])) {
                            echo " selected='selected'";
                        }
                        echo ">" . $exp_tags['tag'] . " (" . $exp_tags['nbtag'] . ")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class='col-md-3' id='tag_db'>
                <label for='tag_db'><?php echo _('With the tag'); ?></label>
                <select name='tag_db'>
                    <option value=''><?php echo _('Select a tag'); ?></option>
                    <?php // Database items types
                    $sql = "SELECT tag, COUNT(id) as nbtag FROM items_tags GROUP BY tag ORDER BY tag ASC";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'team' => $_SESSION['team_id']
                    ));
                    while ($items_types = $req->fetch()) {
                        echo "<option value='" . $items_types['tag'] . "'";
                        // item get selected if it is in the search url
                        if (isset($_GET['tag_db']) && ($items_types['tag'] == $_GET['tag_db'])) {
                            echo " selected='selected'";
                        }
                        echo ">" . $items_types['tag'] . " (" . $items_types['nbtag'] . ")</option>";
                    }
                    ?>
                </select>
            </div>
            <!-- END SEARCH WITH TAG -->
            <!-- SEARCH ONLY -->
            <div class='col-md-6'>
                <label for'searchonly'><?php echo _('Search only in experiments owned by:'); ?> </label><br>
                <select id='searchonly' name='owner'>
                    <option value=''><?php echo _('Yourself'); ?></option>
                    <option disabled>----------------</option>
                    <?php
                    $users_sql = "SELECT userid, firstname, lastname FROM users WHERE team = :team ORDER BY firstname ASC";
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
                <input name="all" id='all_experiments_chkbx' value="y" type="checkbox" <?php
                    // keep the box checked if it was checked
                    if (isset($_GET['all'])) {
                        echo "checked=checked";
                    }
?>>
                <label for='all_experiments_chkbx'><?php echo _("search in everyone's experiments"); ?> </label>
            </div>
            <!-- END SEARCH ONLY -->
        </div>

        <div class='row'>
            <!-- SEARCH DATE -->
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
                    $sql = "SELECT id, name, color FROM status WHERE team = :team_id ORDER BY name ASC";
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
    // TITLE
    if (isset($_GET['title']) && !empty($_GET['title'])) {
        // check if there is a space in the query
        if (strrpos(trim($_GET['title']), " ") !== false) {
            $title_arr = explode(' ', trim($_GET['title']));
            $title = '';
        } else {
            $title = filter_var(trim($_GET['title']), FILTER_SANITIZE_STRING);
        }
    } else { // no title input
        $title = '';
    }

    // BODY
    if (isset($_GET['body']) && !empty($_GET['body'])) {
        if (strrpos(trim($_GET['body']), " ") !== false) {
            $body_arr = explode(' ', trim($_GET['body']));
            $body = '';
        } else {
            $body = filter_var(check_body(trim($_GET['body'])), FILTER_SANITIZE_STRING);
        }
    } else { // no body input
        $body = '';
    }

    // FROM
    if (isset($_GET['from']) && !empty($_GET['from'])) {
        $from = check_date($_GET['from']);
    } else {
        $from = '';
    }

    // TO
    if (isset($_GET['to']) && !empty($_GET['to'])) {
        $to = check_date($_GET['to']);
    } else {
        $to = '';
    }

    // TAGS
    if (isset($_GET['tag_exp']) && !empty($_GET['tag_exp']) && isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $tags = filter_var($_GET['tag_exp'], FILTER_SANITIZE_STRING);
    } elseif (isset($_GET['tag_db']) && !empty($_GET['tag_db']) && isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] !== 'experiments') {
        $tags = filter_var($_GET['tag_db'], FILTER_SANITIZE_STRING);
    } else {
        $tags = '';
    }

    // STATUS
    if (isset($_GET['status']) && !empty($_GET['status']) && is_pos_int($_GET['status'])) {
        $status = $_GET['status'];
    } else {
        $status = '';
    }

    // RATING
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
            $rating = '0';
        } else {
            $rating = intval($_GET['rating']);
        }
    } else {
        $rating = '';
    }

    // OWNER
    if (isset($_GET['owner']) && !empty($_GET['owner']) && is_pos_int($_GET['owner'])) {
        $owner_search = true;
        $owner = $_GET['owner'];
    } else {
        $owner_search = false;
        $owner = '';
    }

    // PREPARE SQL query
    if (isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $tb = 'exp';
        $tbt = 'exptag';
    } else {
        $tb = 'i';
        $tbt = 'itag';
    }

    $sqlGroup = " GROUP BY $tb.id";

    // Title search
    if (!empty($title)) {
        $sqlTitle = " AND $tb.title LIKE '%$title%'";
    } elseif (isset($title_arr)) {
        $sqlTitle = " AND (";
        foreach ($title_arr as $key => $value) {
            if ($key != 0) {
                $sqlTitle .= " OR ";
            }
            $sqlTitle .= "$tb.title LIKE '%$value%'";
        }
        $sqlTitle .= ")";
    } else {
        $sqlTitle = "";
    }

    // Body search
    if (!empty($body)) {
        $sqlBody = " AND $tb.body LIKE '%$body%'";
    } elseif (isset($body_arr)) {
        $sqlBody = " AND (";
        foreach ($body_arr as $key => $value) {
            if ($key != 0) {
                $sqlBody .= " OR ";
            }
            $sqlBody .= "$tb.body LIKE '%$value%'";
        }
        $sqlBody .= ")";
    } else {
        $sqlBody = "";
    }

    // Tag search
    if (!empty($tags)) {
        $sqlTag = " AND $tb.id = $tbt.item_id AND $tbt.tag = '$tags'";
    } else {
        $sqlTag = "";
    }

    // Status search
    if (!empty($status)) {
        $sqlStatus = " AND $tb.status LIKE '$status'";
    } else {
        $sqlStatus = "";
    }

    // Rating search
    if (!empty($rating)) {
        $sqlRating = " AND $tb.rating LIKE '$rating'";
    } else {
        $sqlRating = "";
    }

    // Date search
    if (!empty($from) && !empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '$from' AND '$to'";
    } elseif (!empty($from) && empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '$from' AND '99991212'";
    } elseif (empty($from) && !empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '00000101' AND '$to'";
    } else {
        $sqlDate = "";
    }

    // EXPERIMENT SEARCH
    if (isset($_GET['type'])) {
        if ($_GET['type'] === 'experiments') {

            if (isset($_GET['all']) && !empty($_GET['all'])) {
                $sqlFirst = " $tb.team = " . $_SESSION['team_id'];
            } else {
                $sqlFirst = " $tb.userid = :userid";
            }

            // if you select from two tables but one is empty, as it makes a cross join, no results will be returned
            // on a fresh install, if there is no tags, it will not find anything
            // so we make a left join
            // https://stackoverflow.com/questions/3171276/select-multiple-tables-when-one-table-is-empty-in-mysql
            $sql = "SELECT exp.* FROM experiments as exp LEFT JOIN experiments_tags as exptag ON 1=1 WHERE" . $sqlFirst . $sqlTitle . $sqlBody . $sqlTag . $sqlStatus . $sqlDate . $sqlGroup;
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
            // This counts the number of results
            // and if there wasn't any it gives them a little message explaining that
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

                <div class='align_right'>
                    <a name='anchor'></a>
                    <p class='inline'><?php echo _('Export this result:'); ?> </p>
                    <a href='make_zip.php?id=<?php echo $results_id_str; ?>&type=experiments'>
                        <img src='img/zip.png' title='make a zip archive' alt='zip' />
                    </a>

                    <a href='make_csv.php?id=<?php echo $results_id_str; ?>&type=experiments'>
                        <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' />
                    </a>
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
        } elseif (is_pos_int($_GET['type']) || $_GET['type'] === 'database') {
            // we want only stuff from our team
            $sqlTeam = " AND i.team = " . $_SESSION['team_id'];

            // display entire team database
            if ($_GET['type'] === 'database' &&
                empty($title) &&
                empty($body) &&
                empty($tags) &&
                empty($status) &&
                empty($rating) &&
                empty($from) &&
                empty($to)) {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN items_tags as itag ON 1=1 WHERE i.id > 0";

            } elseif ($_GET['type'] === 'database') {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN items_tags as itag ON 1=1 WHERE i.id > 0";

            } else {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN  items_tags as itag ON 1=1 WHERE type = :type";
            }

            $sql = $sqlFirst . $sqlTeam . $sqlTitle . $sqlBody . $sqlTag . $sqlRating . $sqlDate . $sqlGroup;
            $req = $pdo->prepare($sql);
            if ($_GET['type'] === 'database') {
                $req->execute();
            } else {
                $req->execute(array(
                    'type' => $_GET['type']
                ));
            }

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
    // I added !isset(get[type]) to avoid showing tab_db if we just got to the page
    if ((isset($_GET['type']) && $_GET['type'] == 'experiments') || !isset($_GET['type'])) {
        echo '$("#tag_db").hide();';
    } else {
        echo '$("#tag_exp").hide();';
    }
    ?>

    $('#searchin').on('change', function() {
        if(this.value == 'experiments'){
            $("#tag_exp").show();
            $("#tag_db").hide();
        }else{
            $("#tag_exp").hide();
            $("#tag_db").show();
        }
    });
<?php
// scroll to anchor if there is a search
if (isset($_GET)) {
    echo "location.hash = '#anchor';";
}?>
});
</script>

<?php
require_once('inc/footer.php');
