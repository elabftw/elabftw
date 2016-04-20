<?php
/**
 * inc/head.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="Nicolas CARPi" />
<meta name='referrer' content='origin'>
<link rel="icon" type="image/ico" href="img/favicon.ico" />
<?php
echo "<title>" . (isset($page_title) ? $page_title : "Lab manager") . " - eLab " . Tools::getFtw() . "</title>";
?>
<!-- CSS -->
<!-- Bootstrap -->
<link rel="stylesheet" media="all" href="js/bootstrap/dist/css/bootstrap.min.css">
<link rel="stylesheet" media="all" href="css/main.min.css" />
<link rel="stylesheet" media="all" href="js/colorpicker/jquery.colorpicker.css" />
<link rel="stylesheet" media="all" href="js/fancybox/source/jquery.fancybox.css" />

<link rel="stylesheet" media="all" href="js/jquery-ui/themes/smoothness/jquery-ui.min.css" />
<!-- JAVASCRIPT -->
<script src="js/jquery/dist/jquery.min.js"></script>
<script src="js/jquery-ui/jquery-ui.min.js"></script>
<!-- see Gruntfile.js to see what gets inside this -->
<script src="js/elabftw.min.js"></script>
<!-- bootstrap JS -->
<script src="js/bootstrap/js/alert.js"></script>
<script src="js/bootstrap/js/dropdown.js"></script>
<script src="js/bootstrap/js/button.js"></script>
</head>

<body>
<section id="container" class='container'>

<?php
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    echo "<nav>";
    // to redirect to the right page
    if ($selected_menu === 'Database') {
        $action_target = 'database.php';
    } else {
        $action_target = 'experiments.php';
    }
    ?>
    <form id='big_search' method='get' action='<?php echo $action_target; ?>'>
    <input id='big_search_input' type='search' name='q' size='50' value='<?php
    if (isset($_GET['q'])) {
        echo filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    }
    ?>' />
    </form>
    <span id='logonav'>elab<span class='strong' style='color:white;'>FTW</span></span>
    <?php
    echo "<a href='experiments.php?mode=show'";
    if ($selected_menu == 'Experiments') {
        echo " class='selected'";
    }
    echo ">" . ngettext('Experiment', 'Experiments', 2) . "</a> ";
    echo "<a href='database.php?mode=show'";
    if ($selected_menu == 'Database') {
        echo " class='selected'";
    }
    echo ">" . _('Database') . "</a> ";

    echo "<a href='team.php'";
    if ($selected_menu == 'Team') {
        echo " class='selected'";
    }
    echo ">" . _('Team') . "</a> ";

    echo "<a href='search.php'";
    if ($selected_menu == 'Search') {
        echo " class='selected'";
    }
    echo ">" . _('Search') . "</a> ";

    echo "<a href='" . get_team_config('link_href') . "' target='_blank'>" . get_team_config('link_name') . "</a>";

    echo "</nav>";
} else { // not logged in, show only logo, no menu
    echo "<nav><span id='logonav' class='navleft'>elab<strong>FTW</strong></span></nav>";
}
?>
<div id='real_container'>
<?php
if (isset($_SESSION['auth'])) {
    ?>
    <div>
        <?php echo _('Howdy,') . ' '; ?><a href='profile.php' title='<?php echo _('Profile'); ?>'><?php echo $_SESSION['firstname']; ?></a><br>
        <a href='ucp.php'><img src='img/settings.png' alt='<?php echo _('Settings'); ?>' title='<?php echo _('Settings'); ?>' /></a> |
        <a href='app/logout.php'><img src='img/logout.png' alt='<?php echo _('Logout'); ?>' title='<?php echo _('Logout'); ?>' /></a>
    </div>
    <?php
}
?>
<noscript><!-- show warning if javascript is disabled -->
<div class='alert alert-danger'>
    <p><strong>Javascript is disabled.</strong> Please enable Javascript to view this site in all its glory. Thank You.</p>
</div>
</noscript>
<!-- TITLE -->
<h2><?php echo $page_title; ?></h2>

<?php
// INFO BOX
if (isset($_SESSION['ko']) && is_array($_SESSION['ko']) && count($_SESSION['ko']) > 0) {
    foreach ($_SESSION['ko'] as $msg) {
        display_message('ko', $msg);
    }
    unset($_SESSION['ko']);
}

if (isset($_SESSION['ok']) && is_array($_SESSION['ok']) && count($_SESSION['ok']) > 0) {
    foreach ($_SESSION['ok'] as $msg) {
        display_message('ok', $msg);
    }
    unset($_SESSION['ok']);
}
