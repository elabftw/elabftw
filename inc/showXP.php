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
$load_more_button = "<div class='center'>
        <button class='button' id='loadButton'>"._('Load more') . "</button>
        </div>";
// array to store results;
$results_arr = array();
?>
<menu class='border'>
    <a href="app/create_item.php?type=exp"><img src="img/add.png" class='bot5px' alt="" /> <?php echo _('Create experiment'); ?></a> | 
    <a href='#' class='trigger'><img src="img/add-template.png" class='bot5px' alt="" /> <?php echo _('Create from template'); ?></a>

    <!-- 'FILTER _('Status')' dropdown menu -->
    <span class='align_right'>
    <select onchange=go_url(this.value)><option value=''><?php echo _('Filter status'); ?></option>
    <?php
    $sql = "SELECT id, name FROM status WHERE team = :team_id";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'team_id' => $_SESSION['team_id']
    ));
    while ($status = $req->fetch()) {
        echo "<option value='search.php?type=experiments&status=" . $status['id'] . "'>";
        echo $status['name'] . "</option>";
    }
    ?>
    </select></span>
</menu>

<!-- TEMPLATE CONTAINER -->
<div class='toggle_container'><ul>
<?php // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = :userid";
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
$display = $_SESSION['prefs']['display'];
$order = $_SESSION['prefs']['order'];
$sort = $_SESSION['prefs']['sort'];
$limit = $_SESSION['prefs']['limit'];


// SQL for showXP
// reminder : order by and sort must be passed to the prepare(), not during execute()
// /////////////////
// SEARCH
// /////////////////
if (isset($_GET['q'])) { // if there is a query
    $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);

    $results_arr = search_item('xp', $query, $_SESSION['userid']);

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
    if (count($results_arr) == 0) {
        display_message('error_nocross', _("Sorry. I couldn't find anything :("));
    } else {
        echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time . " " . $unit . ")</p>";
    }

    // loop the results array and display results
    foreach ($results_arr as $result_id) {
        showXP($result_id, $display);
    }

    // show load more button if there are more results than the default display number
    if (count($results_arr) > $limit) {
        echo $load_more_button;
    }

// /////////////
// RELATED
// /////////////
} elseif (isset($_GET['related']) && is_pos_int($_GET['related'])) {// search for related experiments to DB item id
    $item_id = $_GET['related'];
    // search in title date and body
    $sql = "SELECT item_id FROM experiments_links 
        WHERE link_id = :link_id LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'link_id' => $item_id
    ));
    // put resulting ids in the results array
    while ($data = $req->fetch()) {
        $results_arr[] = $data['item_id'];
    }
    $req->closeCursor();
    // show number of results found and time
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
    if (count($results_arr) > 1) {
        echo "<p class='smallgray'>" . count($results_arr) . " " . _('results.') . " ($total_time $unit)</p>";
    } elseif (count($results_arr) == 1) {
        echo "<p class='smallgray'>" . _('Found') . " ($total_time $unit)</p>";
    } else {
        display_message('error', _('Found'));
    }

    // loop the results array and display results
    foreach ($results_arr as $result_id) {
        showXP($result_id, $display);
    } // end foreach

    // show load more button if there are more results than the default display number
    if (count($results_arr) > $limit) {
        echo $load_more_button;
    }

///////////////
// TAG SEARCH
///////////////
} elseif (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
    $sql = "SELECT item_id, userid FROM experiments_tags
    WHERE tag LIKE :tag AND userid = :userid";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'tag' => $tag,
        'userid' => $_SESSION['userid']
    ));
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
    if (count($results_arr) == 0) {
        display_message('error_nocross', _("Sorry. I couldn't find anything :("));
    } else {
        echo "<p class='smallgray'>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time . " " . $unit . ")</p>";
    }

    // clean duplicates
    $results_arr = array_unique($results_arr);
    // loop the results array and display results
    foreach ($results_arr as $result_id) {
        showXP($result_id, $display);
    } // end foreach

    // show load more button if there are more results than the default display number
    if (count($results_arr) > $limit) {
        echo $load_more_button;
    }

// /////////////////
// DEFAULT VIEW
// /////////////////
} else {
    $sql = "SELECT id, date, title 
        FROM experiments 
        WHERE userid = :userid 
        ORDER BY $order $sort 
        LIMIT 100";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
    $req->execute();
    $count = $req->rowCount();
    // If there are no experiments, display a little message
    if ($count == 0) {
        display_message('info_nocross', sprintf(_("<strong>Welcome to eLabFTW.</strong> Click the %sCreate experiment%s button to get started."), "<img src='img/add.png' alt='' /><a class='alert-link' href='app/create_item.php?type=exp'>", "</a>"));
    } else {
        while ($experiments = $req->fetch()) {
            $results_arr[] = $experiments['id'];
        }
        $req->closeCursor();
        // loop the results array and display results
        foreach ($results_arr as $result_id) {
            showXP($result_id, $display);
        } // end foreach

        // show load more button if there are more results than the default display number
        if (count($results_arr) > $limit) {
            echo $load_more_button;
        }
    }
} // END CONTENT
?>


<script>
// READY ? GO !
function go_url(x) {
    if(x == '') {
        return;
    }
    location = x;
}
$(document).ready(function(){

    // SHOW MORE _('Experiment')S BUTTON
    $('section.item').hide(); // hide everyone
    $('section.item').slice(0, <?php echo $limit; ?>).show(); // show only the default at the beginning
    $('#loadButton').click(function(e){ // click to load more
        e.preventDefault();
        $("section.item:hidden").slice(0, <?php echo $limit; ?>).show();
        if ($("section.item:hidden").length == 0) { // check if there are more exp to show
            $('#loadButton').hide(); // hide load button when there is nothing more to show
        }
    });

    // _('Experiment')S TEMPLATE HIDDEN DIV
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('div.toggle_container').slideToggle(1);
	});
    // KEYBOARD _('Shortcut')S
    key('<?php echo $_SESSION['prefs']['shortcuts']['create']; ?>', function(){
        location.href = 'app/create_item.php?type=exp'
        });
    });
</script>
