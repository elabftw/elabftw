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
if (isset($_SESSION['prefs']['display'])) {
    $display = $_SESSION['prefs']['display'];
} else {
    $display = 'default';
}

// keep tag var in url
$getTag = '';
if (isset($_GET['tag']) && $_GET['tag'] != '') {
    $getTag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
}


// SQL to get items name
$sql = "SELECT * FROM items_types WHERE team = :team ORDER BY name ASC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'team' => $_SESSION['team_id']
));
?>

<menu class='border'>
    <div class="row">
        <div class="col-md-6">
            <form class="form-inline pull-left">
                <?php
                // CREATE NEW dropdown menu
                echo "<select class='form-control select-create-db' onchange='go_url(this.value)'>
                <option value=''>" . _('Create new') . "</option>";
                while ($items_types = $req->fetch()) {
                    echo "<option value='app/create_item.php?type=" . $items_types['id'] . "' name='type' ";
                    echo ">" . $items_types['name'] . "</option>";
                }
                echo "</select>";
                ?>
            </form>
        </div>
        <div class="col-md-6">
            <form class="form-inline pull-right">
                <div class="form-group">
                    <input type="hidden" name="mode" value="show" />
                    <input type="hidden" name="tag" value="<?php echo $getTag; ?>" />
                    <!-- ORDER / SORT dropdown menu -->
                    <select name="order" class="form-control select-order">
                        <option value=''><?php echo _('Order by'); ?></option>
                        <option value='cat'<?php checkSelectOrder('cat'); ?>><?php echo _('Category'); ?></option>
                        <option value='date'<?php checkSelectOrder('date'); ?>><?php echo _('Date'); ?></option>
                        <option value='rating'<?php checkSelectOrder('rating'); ?>><?php echo _('Rating'); ?></option>
                        <option value='title'<?php checkSelectOrder('title'); ?>><?php echo _('Title'); ?></option>
                    </select>
                    <select name="sort" class="form-control select-sort">
                        <option value=''><?php echo _('Sort'); ?></option>
                        <option value='desc'<?php checkSelectSort('desc'); ?>><?php echo _('DESC'); ?></option>
                        <option value='asc'<?php checkSelectSort('asc'); ?>><?php echo _('ASC'); ?></option>
                    </select>
                    <button class="btn btn-elab submit-order"><?php echo _('Order'); ?></button>
                    <select name="filter" class="form-control select-filter-cat">
                        <option value=""><?php echo _('Filter type'); ?></option>
                    <?php
                    // we do the request again to get the list again
                    $req->execute();
                    while ($items_types = $req->fetch()) {
                        echo "
                        <option value='" . $items_types['id'] . "'" . checkSelectFilter($items_types['id']) . ">" . $items_types['name'] . "</option>";
                    }
                    ?>
                    </select>
                    <button class="btn btn-elab submit-filter"><?php echo _('Filter'); ?></button>
                    <button type="reset" class="btn btn-danger submit-reset" onclick="javascript:location.href='database.php?mode=show&tag=<?php echo $getTag; ?>';"><?php echo _('Reset'); ?></button>
                </div>
            </form>
        </div>
    </div>
</menu>
<!-- end menu -->

<?php
$order = 'it.id';
$sort = 'DESC';
$filter = '';

// REPLACE WITH ORDER
if (isset($_GET['order'])) {
    if ($_GET['order'] != '') {
        if ($_GET['order'] === 'cat') {
            $order = 'ty.name';
        } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
            $order = 'it.' . $_GET['order'];
        } else {
            $message = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#17", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
            display_message('error', $message);
            exit;
        }
    }
}

if (isset($_GET['sort'])) {
    if ($_GET['sort'] != '' && ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc')) {
        $sort = $_GET['sort'];
    }
}

if (isset($_GET['filter'])) {
    if ($_GET['filter'] != '' && is_pos_int($_GET['filter'])) {
        $filter = "AND ty.id = '" . $_GET['filter'] . "' ";
    }
}

// SQL for showDB
// TAG SEARCH
if (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
    $sql = "SELECT it.id, ty.name, ta.item_id 
    FROM items AS it, items_types AS ty, items_tags AS ta 
    WHERE it.type = ty.id 
    AND it.team = :teamid 
    AND it.id = ta.item_id 
    AND ta.tag LIKE :tag 
    " . $filter . "
    ORDER BY $order $sort 
    LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->bindParam(':tag', $tag, PDO::PARAM_STR);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();

    $results_arr = array();
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }

    // show number of results found
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    $finish = $time;
    $total_time = round(($finish - $start), 4);
    $unit = 'seconds';
    if ($total_time < 0.01) {
        $total_time = $total_time * 1000;
        $unit = 'milliseconds';
    }

    // show number of results found
    if (count($results_arr) == 0) {
        display_message('error_nocross', _("Sorry. I couldn't find anything :("));
    } else {
        echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time . " " . $unit . ")</p>";
    }

    // clean duplicates
    $results_arr = array_unique($results_arr);
    // loop the results array and display results
    foreach ($results_arr as $result_id) {
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
    if (count($results_arr) == 0) {
        display_message('error_nocross', _("Sorry. I couldn't find anything :("));
    } else {
        echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time . " " . $unit . ")</p>";
    }

    // loop the results array and display results
    foreach ($results_arr as $result_id) {
        showDB($result_id, $display);
    }
// end if there is a search
} else { // there is no search
    $sql = "SELECT it.id, ty.name 
    FROM items AS it, items_types AS ty 
    WHERE it.type = ty.id 
    AND it.team = :teamid 
    " . $filter . "
    ORDER BY $order $sort 
    LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    $count = $req->rowCount();
    if ($count == 0) {
        // it might be a fresh install, but it might also be the search filters are too restrictive
        if (isset($_GET['tag'])) {
            display_message('error_nocross', _("Sorry. I couldn't find anything :("));
        } else {
            display_message('info', _('<strong>Welcome to eLabFTW.</strong> Select an item in the «Create new» list to begin filling your database.'));
        }
    } else {
        $results_arr = array();
        while ($final_query = $req->fetch()) {
            $results_arr[] = $final_query['id'];
        }
        // loop the results array and display results
        echo "<p>" . _('Showing last uploads:') . "</p>";
        foreach ($results_arr as $result_id) {
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
