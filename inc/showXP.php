<?php
/**
 * inc/showXP.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
use \Elabftw\Elabftw\Tools as Tools;

$results_arr = array();
$load_more_button = "<div class='center'>
        <button class='button' id='loadButton'>"._('Load more') . "</button>
        </div>";

// keep tag var in url
$getTag = '';
if (isset($_GET['tag']) && $_GET['tag'] != '') {
    $getTag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
}
?>
<menu class='border'>
    <div class="row">
        <div class="col-md-4">
            <a href="app/create_item.php?type=exp" id='createExperiment'><img src="img/add.png" class='bot5px' alt="" /> <?php echo _('Create experiment'); ?></a> |
            <a href='#' class='trigger'><img src="img/add-template.png" class='bot5px' alt="" /> <?php echo _('Create from template'); ?></a>
        </div>
        <div class="col-md-8">
            <form class="form-inline pull-right">
                <div class="form-group">
                    <input type="hidden" name="mode" value="show" />
                    <input type="hidden" name="tag" value="<?php echo $getTag; ?>" />
                    <!-- FILTER STATUS dropdown menu -->
                    <select name="filter" class="form-control select-filter-status">
                        <option value=''><?php echo _('Filter status'); ?></option>
                    <?php
                    $sql = "SELECT id, name FROM status WHERE team = :team_id ORDER BY name ASC";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'team_id' => $_SESSION['team_id']
                    ));
                    while ($status = $req->fetch()) {
                        echo "
                        <option value='" . $status['id'] . "'" . checkSelectFilter($status['id']) . ">" . $status['name'] . "</option>";
                    }
                    ?>
                    </select>
                    <button class="btn btn-elab submit-filter"><?php echo _('Filter'); ?></button>
                    <!-- ORDER / SORT dropdown menu -->
                    <select name="order" class="form-control select-order">
                        <option value=''><?php echo _('Order by'); ?></option>
                        <option value='date'<?php checkSelectOrder('date'); ?>><?php echo _('Date'); ?></option>
                        <option value='status'<?php checkSelectOrder('status'); ?>><?php echo ngettext('Status', 'Status', 1); ?></option>
                        <option value='title'<?php checkSelectOrder('title'); ?>><?php echo _('Title'); ?></option>
                    </select>
                    <select name="sort" class="form-control select-sort">
                        <option value=''><?php echo _('Sort'); ?></option>
                        <option value='desc'<?php checkSelectSort('desc'); ?>><?php echo _('DESC'); ?></option>
                        <option value='asc'<?php checkSelectSort('asc'); ?>><?php echo _('ASC'); ?></option>
                    </select>
                    <button class="btn btn-elab submit-order"><?php echo _('Order'); ?></button>
                    <button type="reset" class="btn btn-danger submit-reset" onclick="javascript:location.href='experiments.php?mode=show&tag=<?php echo $getTag; ?>';"><?php echo _('Reset'); ?></button>
                </div>
            </form>
        </div>
    </div>
</menu>
<!-- TEMPLATE CONTAINER -->
<div class='toggle_container'><ul>
<?php // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = :userid ORDER BY ordering ASC";
$tplreq = $pdo->prepare($sql);
$tplreq->bindParam(':userid', $_SESSION['userid']);
$tplreq->execute();
if ($tplreq->rowCount() > 0) {
    while ($tpl = $tplreq->fetch()) {
        echo "<a href='app/create_item.php?type=exp&tpl=" . $tpl['id'] . "' class='badge'>" . $tpl['name'] . "</a>";
    }
} else { // user has no templates
    display_message('warning_nocross', sprintf(_("<strong>You do not have any templates yet.</strong> Go to %syour control panel%s to make one !"), "<a class='alert-link' href='ucp.php?tab=3'>", "</a>"));
}
?>
</ul></div>
<?php
// VIEWING PREFS //
$order = filter_var($_SESSION['prefs']['order'], FILTER_SANITIZE_STRING);
$sort = filter_var($_SESSION['prefs']['sort'], FILTER_SANITIZE_STRING);
$limit = filter_var($_SESSION['prefs']['limit'], FILTER_SANITIZE_STRING);
$filter = '';

// REPLACE WITH ORDER
if (isset($_GET['order'])) {
    if ($_GET['order'] != '') {
        if ($_GET['order'] === 'status') {
            $order = 'st.name';
        } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
            $order = 'ex.' . $_GET['order'];
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
        $filter = "AND st.id = '" . $_GET['filter'] . "' ";
    }
}

// SQL for showXP
// reminder : order by and sort must be passed to the prepare(), not during execute()
// SEARCH
if (isset($_GET['q'])) { // if there is a query
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);

    $results_arr = search_item('xp', $query, $_SESSION['userid']);

// RELATED
} elseif (isset($_GET['related']) && is_pos_int($_GET['related'])) {// search for related experiments to DB item id
    $item_id = $_GET['related'];
    // search in title date and body
    $sql = "SELECT item_id FROM experiments_links
        WHERE link_id = :link_id LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'link_id' => $item_id
    ));
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }

// TAG SEARCH
} elseif (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
    $sql = "SELECT ex.id, ex.date, ex.title, st.name, ta.item_id
        FROM experiments AS ex, experiments_tags AS ta, status AS st
        WHERE ex.userid = :userid
        AND ta.userid = :userid
        AND ex.status = st.id
        AND st.team = :teamid
        AND ex.id = ta.item_id
        AND ta.tag LIKE :tag
        " . $filter . "
        ORDER BY $order $sort
        LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->bindParam(':tag', $tag, PDO::PARAM_STR);
    $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }

// DEFAULT VIEW
} else {
    $search_type = 'none';

    $sql = "SELECT ex.id, ex.date, ex.title, st.name
        FROM experiments AS ex, status AS st
        WHERE ex.userid = :userid
        AND ex.status = st.id
        AND st.team = :teamid
        " . $filter . "
        ORDER BY $order $sort
        LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
    $req->bindParam(':teamid', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    while ($get_id = $req->fetch()) {
        $results_arr[] = $get_id['id'];
    }
}

// clean duplicates
$results_arr = array_unique($results_arr);

$total_time = get_total_time();

if (count($results_arr) === 0 && $search_type != 'none') {
    display_message('error_nocross', _("Sorry. I couldn't find anything :("));
// Display message for fresh install
} elseif (count($results_arr) === 0 && $search_type === 'none') {
    display_message('info_nocross', sprintf(_("<strong>Welcome to eLabFTW.</strong> Click the %sCreate experiment%s button to get started."), "<img src='img/add.png' alt='' /><a class='alert-link' href='app/create_item.php?type=exp'>", "</a>"));
} else {
    ?>
    <div class='align_right'>
        <a name='anchor'></a>
        <p class='inline'><?php echo _('Export this result:'); ?> </p>
        <a href='make.php?what=zip&id=<?php echo Tools::buildStringFromArray($results_arr); ?>&type=experiments'>
            <img src='img/zip.png' title='make a zip archive' alt='zip' />
        </a>

        <a href='make.php?what=csv&id=<?php echo Tools::buildStringFromArray($results_arr); ?>&type=experiments'>
            <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' />
        </a>
    </div>
    <?php
    echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time['time'] . " " . $total_time['unit'] . ")</p>";
    // loop the results array and display results
    foreach ($results_arr as $result_id) {
        showXP($result_id, $_SESSION['prefs']['display']);
    } // end foreach

    // show load more button if there are more results than the default display number
    if (count($results_arr) > $limit) {
        echo $load_more_button;
    }
} // END CONTENT
?>

<script>
// READY ? GO !
$(document).ready(function(){

    // SHOW MORE EXPERIMENTS BUTTON
    $('section.item').hide(); // hide everyone
    $('section.item').slice(0, <?php echo $limit; ?>).show(); // show only the default at the beginning
    $('#loadButton').click(function(e){ // click to load more
        e.preventDefault();
        $("section.item:hidden").slice(0, <?php echo $limit; ?>).show();
        if ($("section.item:hidden").length == 0) { // check if there are more exp to show
            $('#loadButton').hide(); // hide load button when there is nothing more to show
        }
    });

    // EXPERIMENTS TEMPLATE HIDDEN DIV
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('div.toggle_container').slideToggle(1);
	});
    // KEYBOARD SHORTCUTS
    key('<?php echo $_SESSION['prefs']['shortcuts']['create']; ?>', function(){
        location.href = 'app/create_item.php?type=exp'
        });
    });
</script>
