<?php
/**
 * search.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;

/**
 * The search page
 * Here be dragons!
 *
 */
require_once 'app/init.inc.php';
$page_title = _('Search');
$selected_menu = 'Search';
require_once 'app/head.inc.php';

$Experiments = new Experiments($_SESSION['team_id'], $_SESSION['userid']);
$Database = new Database($_SESSION['team_id'], $_SESSION['userid']);
$ItemsTypes = new ItemsTypes($_SESSION['team_id']);
$Status = new Status($_SESSION['team_id']);
$Users = new Users();

// TYPE
if (isset($_GET['type']) && $_GET['type'] === 'database') {
    $seldb = " selected='selected'";
} else {
    $seldb = "";
}

// FROM
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from = Tools::kdate($_GET['from']);
} else {
    $from = '';
}

// TO
if (isset($_GET['to']) && !empty($_GET['to'])) {
    $to = Tools::kdate($_GET['to']);
} else {
    $to = '';
}

$title = '';
$titleWithSpace = false;
// TITLE
if (isset($_GET['title']) && !empty($_GET['title'])) {
    // check if there is a space in the query
    if (strrpos(trim($_GET['title']), " ") !== false) {
        $title_arr = explode(' ', trim($_GET['title']));
        $titleWithSpace = true;
    }
    $title = filter_var(trim($_GET['title']), FILTER_SANITIZE_STRING);
}

// BODY
$body = '';
$bodyWithSpace = false;
if (isset($_GET['body']) && !empty($_GET['body'])) {
    if (strrpos(trim($_GET['body']), " ") !== false) {
        $body_arr = explode(' ', trim($_GET['body']));
        $bodyWithSpace = true;
    }
    $body = filter_var(Tools::checkBody(trim($_GET['body'])), FILTER_SANITIZE_STRING);
}

// ANDOR
$andor = ' AND ';
if (isset($_GET['andor']) && ($_GET['andor'] === 'and')) {
    $andSel = " selected='selected'";
} else {
    $andSel = '';
}
if (isset($_GET['andor']) && ($_GET['andor'] === 'or')) {
    $orSel = " selected='selected'";
    $andor = ' OR ';
} else {
    $orSel = '';
}
?>

<!-- Search page begin -->
<section class='searchform box'>
    <form name="search" method="get" action="search.php">
        <div class='row'>
            <!-- SEARCH IN-->
            <div class='col-md-3'>
                <label for='searchin'><?= _('Search in') ?></label>
                <select name='type' id='searchin'>
                    <option value='experiments'><?= ngettext('Experiment', 'Experiments', 2) ?></option>
                    <option disabled>----------------</option>
                    <option value='database'<?= $seldb ?>><?= _('Database') ?></option>
<?php // Database items types
$itemsTypesArr = $ItemsTypes->readAll();
foreach ($itemsTypesArr as $items_types) {
    echo "<option value='" . $items_types['id'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['type']) && $items_types['id'] == $_GET['type']) {
        echo " selected='selected'";
    }
    echo ">- " . $items_types['name'] . "</option>";
}
?>
                </select>
            </div>
            <!-- END SEARCH IN -->
            <!-- SEARCH WITH TAG -->
            <div class='col-md-3' id='tag_exp'>
                <label for='tag_exp'><?php echo _('With the tag'); ?></label>
                <select name='tag_exp' style='max-width:80%'>
                    <option value=''><?php echo _('Select a Tag'); ?></option>

<?php
$tag_in_url = null;
if (isset($_GET['tag_exp'])) {
    $tag_in_url = $_GET['tag_exp'];
}
if (isset($_GET['tag_db'])) {
    $tag_in_url = $_GET['tag_db'];
}

$Tags = new Tags($Experiments);
echo $Tags->generateTagList('options', $tag_in_url);
?>

                </select>
            </div>

            <div class='col-md-3' id='tag_db'>
                <label for='tag_db'><?php echo _('With the tag'); ?></label>
                <select name='tag_db'>
                    <option value=''><?php echo _('Select a tag'); ?></option>

<?php // Database items types
$Tags = new Tags($Database);
echo $Tags->generateTagList('options', $tag_in_url);
?>
                </select>
            </div>
            <!-- END SEARCH WITH TAG -->

            <!-- SEARCH ONLY -->
            <div class='col-md-6'>
                <label for'searchonly'><?php echo _('Search only in experiments owned by:'); ?> </label><br>
                <!-- when you change this select, you reload the page so the tag selector loads the correct tags -->
                <select id='searchonly' name='owner'>
                    <option value=''><?php echo _('Yourself'); ?></option>
                    <!-- add an option to search in the whole team (owner = 0) -->
                    <option value='0'
<?php
if (isset($_GET['owner']) && ($_GET['owner'] === '0')) {
    echo " selected='selected'";
}
echo ">" . _("All the team"); ?></option>
<option disabled>----------------</option>
<?php
$usersArr = $Users->readAllFromTeam($_SESSION['team_id']);
foreach ($usersArr as $user) {
    echo "<option value='" . $user['userid'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['owner']) && ($user['userid'] == $_GET['owner'])) {
        echo " selected='selected'";
    }
    echo ">" . $user['fullname'] . "</option>";
}
?>
                </select><br>
            </div>
            <!-- END SEARCH ONLY -->
        </div>

        <!-- SEARCH DATE -->
        <div class='row'>
            <div class='col-md-8'>
                <label for='from'><?= _('Where date is between') ?></label>
                <input id='from' name='from' type='text' size='8' class='datepicker' value='<?= $from ?>'/>
                <label span style='margin:0 10px;' for='to'> <?php echo _('and'); ?> </label>
                <input id='to' name='to' type='text' size='8' class='datepicker' value='<?= $to ?>'/>
            </div>
        </div>
        <!-- END SEARCH DATE -->

        <!-- TITLE -->
        <div class='row'>
            <div class='col-md-6'>
            <label for='title'><?php echo _('And title contains'); ?></label>
            <input id='title' name='title' type='text' value='<?= $title ?>'/>
            </div>
            <!-- STATUS -->
            <div class='col-md-4'>
                <label for='status'><?= _('And status is') ?></label>
                <select id='status' name="status">
                    <option value=''><?= _('select status') ?></option>
<?php
$statusArr = $Status->readAll();
foreach ($statusArr as $status) {
    echo "<option ";
    if (isset($_GET['status']) && ($_GET['status'] == $status['id'])) {
        echo "selected ";
    }
    echo "value='" . $status['id'] . "'>" . $status['name'] . "</option>";
}
?>
                </select>
            </div>

        </div>
        <div class='row'>
            <div class='col-md-6'>
            <label for='body'><?= _('And body contains') ?></label>
            <input id='body' name='body' type='text' value='<?= $body ?>'/>
            <!-- AND / OR -->
                <select id='andor' name='andor'>
                <option value='' disabled selected><?= _('Space means') ?></option>
                <option value='and' <?= $andSel ?>><?= _('and') ?></option>

                <option value='or' <?= $orSel ?>><?= _('or') ?></option>
                </select>
            </div>
            <!-- END TITLE/BODY block -->

            <!-- RATING -->
            <div class='col-md-4'>
                <label for='rating'><?= _('And rating is') ?></label>
                <select id='rating' name='rating'>
                    <option value=''><?= _('select number of stars') ?></option>
                    <option value='no'><?= _('Unrated') ?></option>
<?php
for ($i = 1; $i <= 5; $i++) {
    echo "<option value='" . $i . "'";
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
            <button id='searchButton' class='button' value='Submit' type='submit'><?= _('Launch search') ?></button>
        </div>
    </form>
</section>


<?php
/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if (isset($_GET)) {
    // assign variables from get

    $table = 'items';
    $status = '';
    $rating = '';
    $tags = '';

    // TABLE
    if (isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $table = 'experiments';
    }

    // TAGS
    if (isset($_GET['tag_exp']) && !empty($_GET['tag_exp']) && isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $tags = filter_var($_GET['tag_exp'], FILTER_SANITIZE_STRING);
    } elseif (isset($_GET['tag_db']) && !empty($_GET['tag_db']) && isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] !== 'experiments') {
        $tags = filter_var($_GET['tag_db'], FILTER_SANITIZE_STRING);
    }

    // STATUS
    if (isset($_GET['status']) && !empty($_GET['status']) && Tools::checkId($_GET['status'])) {
        $status = $_GET['status'];
    }

    // RATING
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
            $rating = '0';
        } else {
            $rating = intval($_GET['rating']);
        }
    }

    // PREPARE SQL query
    $sqlUserid = '';
    $sqlDate = '';
    $sqlTitle = '';
    $sqlBody = '';
    $sqlTag = '';
    $sqlStatus = '';
    $sqlRating = '';

    // Title search
    if ($titleWithSpace) {
        $sqlTitle = " AND (";
        foreach ($title_arr as $key => $value) {
            if ($key != 0) {
                $sqlTitle .= $andor;
            }
            $sqlTitle .= $table . ".title LIKE '%$value%'";
        }
        $sqlTitle .= ")";
    } elseif (!empty($title)) {
        $sqlTitle = " AND " . $table . ".title LIKE '%$title%'";
    }

    // Body search
    if ($bodyWithSpace) {
        $sqlBody = " AND (";
        foreach ($body_arr as $key => $value) {
            if ($key != 0) {
                $sqlBody .= $andor;
            }
            $sqlBody .= "$table.body LIKE '%$value%'";
        }
        $sqlBody .= ")";
    } elseif (!empty($body)) {
        $sqlBody = " AND $table.body LIKE '%$body%'";
    }

    // Tag search
    if (!empty($tags)) {
        $sqlTag = " AND $table.id = " . $table . "_tag.item_id AND " . $table . "_tag.tag = '$tags'";
    }

    // Status search
    if (!empty($status)) {
        $sqlStatus = " AND $table.status = '$status'";
    }

    // Rating search
    if (!empty($rating)) {
        $sqlRating = " AND $table.rating LIKE '$rating'";
    }

    // Date search
    if (!empty($from) && !empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '$from' AND '$to'";
    } elseif (!empty($from) && empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '$from' AND '99991212'";
    } elseif (empty($from) && !empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '00000101' AND '$to'";
    }

    /////////////////////////////////////////////////////////////////
    if (isset($_GET['type'])) {
        if ($_GET['type'] === 'experiments') {
            // EXPERIMENTS SEARCH
            $EntityView = new ExperimentsView($Experiments);

            // USERID FILTER
            if (isset($_GET['owner'])) {
                $EntityView->searchType = 'userid';
                if (Tools::checkId($_GET['owner'])) {
                    $owner = $_GET['owner'];
                    $sqlUserid = " AND experiments.userid = " . $owner;
                } elseif (empty($_GET['owner'])) {
                    $owner = $EntityView->Entity->userid;
                    $sqlUserid = " AND experiments.userid = " . $owner;
                }
                if ($_GET['owner'] === '0') {
                    $sqlUserid = '';
                    // read all experiments from team
                    $EntityView->showTeam = true;
                }
            }

            // STATUS
            $EntityView->Entity->categoryFilter = $sqlStatus;

        } else {
            // DATABASE SEARCH
            $EntityView = new DatabaseView($Database);

            // RATING
            $EntityView->Entity->ratingFilter = $sqlRating;
            if (Tools::checkId($_GET['type'])) {
                // filter on database items types
                $EntityView->Entity->categoryFilter = "AND items_types.id = " . $_GET['type'];
                $EntityView->searchType = 'filter';
            }
        }

        // adjust display
        $EntityView->display = $_SESSION['prefs']['display'];
        // we are on the search page, so we don't want any "click here to create your first..."
        $EntityView->searchType = 'something';

        $EntityView->Entity->useridFilter = $sqlUserid;
        $EntityView->Entity->titleFilter = $sqlTitle;
        $EntityView->Entity->dateFilter = $sqlDate;
        $EntityView->Entity->bodyFilter = $sqlBody;

        // DISPLAY RESULTS
        echo "<section style='margin-top:20px'>";
        echo $EntityView->buildShow();
        echo "</section>";
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

    $('#searchonly').on('change', function() {
        insertParamAndReload('owner', $(this).val());
    });

    $('#searchin').on('change', function() {
        if (this.value == 'experiments') {
            $("#tag_exp").show();
            $("#tag_db").hide();
        } else {
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

<?php require_once('app/footer.inc.php');
