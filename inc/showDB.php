<?php
/**
 * inc/showDB.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
use \Elabftw\Elabftw\Tools as Tools;

$itemsTypes = new \Elabftw\Elabftw\ItemsTypes();
$itemsTypesArr = $itemsTypes->read($_SESSION['team_id']);

$results_arr = array();
// keep tag var in url
$getTag = '';
if (isset($_GET['tag']) && $_GET['tag'] != '') {
    $getTag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
}
?>

<menu class='border'>
    <div class="row">
        <div class="col-md-2">
            <form class="form-inline pull-left">
                <?php
                // CREATE NEW dropdown menu
                echo "<select class='form-control select-create-db' onchange='go_url(this.value)'>
                <option value=''>" . _('Create new') . "</option>";
                foreach ($itemsTypesArr as $items_types) {
                    echo "<option value='app/create_item.php?type=" . $items_types['id'] . "' name='type' ";
                    echo ">" . $items_types['name'] . "</option>";
                }
                echo "</select>";
                ?>
            </form>
        </div>
        <div class="col-md-10">
            <form class="form-inline pull-right">
                <div class="form-group">
                    <input type="hidden" name="mode" value="show" />
                    <input type="hidden" name="tag" value="<?php echo $getTag; ?>" />
                    <!-- filter TYPE -->
                    <select name="filter" class="form-control select-filter-cat">
                        <option value=""><?php echo _('Filter type'); ?></option>
                    <?php
                    foreach ($itemsTypesArr as $items_types) {
                        echo "
                        <option value='" . $items_types['id'] . "'" . checkSelectFilter($items_types['id']) . ">" . $items_types['name'] . "</option>";
                    }
                    ?>
                    </select>
                    <button class="btn btn-elab submit-filter"><?php echo _('Filter'); ?></button>
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

// ///////////////////////////////////////////////////////////////////////
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
    ORDER BY $order $sort";
    $req = $pdo->prepare($sql);
    $req->bindParam(':tag', $tag, PDO::PARAM_STR);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();

    // put resulting ids in the results array
    while ($get_id = $req->fetch()) {
        $results_arr[] = $get_id['item_id'];
    }
    $search_type = 'tag';

// NORMAL SEARCH
} elseif (isset($_GET['q']) && !empty($_GET['q'])) {
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    // we make an array for the resulting ids
    $results_arr = array();
    $results_arr = search_item('db', $query, $_SESSION['userid']);
    $search_type = 'normal';

// end if there is a search
} else { // there is no search
    $sql = "SELECT it.id, ty.name
    FROM items AS it, items_types AS ty
    WHERE it.type = ty.id
    AND it.team = :teamid
    " . $filter . "
    ORDER BY $order $sort";
    $req = $pdo->prepare($sql);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    while ($get_id = $req->fetch()) {
        $results_arr[] = $get_id['id'];
    }
    $search_type = 'none';
}

$total_time = get_total_time();

// filter out duplicate ids
$results_arr = array_unique($results_arr);
// show number of results found
if (count($results_arr) === 0 && $search_type != 'none') {
    display_message('ko_nocross', _("Sorry. I couldn't find anything :("));
} elseif (count($results_arr) === 0 && $search_type === 'none') {
    display_message('ok', _('<strong>Welcome to eLabFTW.</strong> Select an item in the «Create new» list to begin filling your database.'));
} else {
    ?>
    <div class='align_right'>
        <a name='anchor'></a>
        <p class='inline'><?php echo _('Export this result:'); ?> </p>
        <a href='make.php?what=zip&id=<?php echo Tools::buildStringFromArray($results_arr); ?>&type=items'>
            <img src='img/zip.png' title='make a zip archive' alt='zip' />
        </a>

        <a href='make.php?what=csv&id=<?php echo Tools::buildStringFromArray($results_arr); ?>&type=items'>
            <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' />
        </a>
    </div>
    <?php
    echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time['time'] . " " . $total_time['unit'] . ")</p>";
}

// loop the results array and display results
foreach ($results_arr as $result_id) {
    showDB($result_id, $_SESSION['prefs']['display']);
}
