<?php
/**
 * inc/head.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
// Random title
$ftw_arr = array();
// Lots of 'For The World' so the other ones appear more rarely
for ($i = 0; $i < 200; $i++) {
    $ftw_arr[] = 'For The World';
}
// Now the fun ones
$ftw_arr[] = 'For Those Wondering';
$ftw_arr[] = 'For The Worms';
$ftw_arr[] = 'Forever Two Wheels';
$ftw_arr[] = 'Free The Wookies';
$ftw_arr[] = 'Forward The Word';
$ftw_arr[] = 'Forever Together Whenever';
$ftw_arr[] = 'Face The World';
$ftw_arr[] = 'Forget The World';
$ftw_arr[] = 'Free To Watch';
$ftw_arr[] = 'Feed The World';
$ftw_arr[] = 'Feel The Wind';
$ftw_arr[] = 'Feel The Wrath';
$ftw_arr[] = 'Fight To Win';
$ftw_arr[] = 'Find The Waldo';
$ftw_arr[] = 'Finding The Way';
$ftw_arr[] = 'Flying Training Wing';
$ftw_arr[] = 'Follow The Way';
$ftw_arr[] = 'For The Wii';
$ftw_arr[] = 'For The Win';
$ftw_arr[] = 'For The Wolf';
$ftw_arr[] = 'Free The Weed';
$ftw_arr[] = 'Free The Whales';
$ftw_arr[] = 'From The Wilderness';
$ftw_arr[] = 'Freedom To Work';
$ftw_arr[] = 'For The Warriors';
$ftw_arr[] = 'Full Time Workers';
$ftw_arr[] = 'Fabricated To Win';
$ftw_arr[] = 'Furiously Taunted Wookies';
$ftw_arr[] = 'Flash The Watch';
shuffle($ftw_arr);
$ftw = $ftw_arr[0];
?>

<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="Nicolas CARPi" />
<link rel="icon" type="image/ico" href="img/favicon.ico" />
<?php
echo "<title>" . (isset($page_title) ? $page_title : "Lab manager") . " - eLab " . $ftw . "</title>";
?>
<!-- CSS -->
<!-- Bootstrap -->
<link rel="stylesheet" media="all" href="js/bootstrap/dist/css/bootstrap.min.css">
<link rel="stylesheet" media="all" href="css/main.min.css" />
<link rel="stylesheet" media="all" href="js/jquery-ui/themes/smoothness/jquery-ui.min.css" />
<!-- JAVASCRIPT -->
<script src="js/jquery/dist/jquery.min.js"></script>
<script src="js/jquery-ui/jquery-ui.min.js"></script>
<!-- see Gruntfile.js to see what gets inside this -->
<script src="js/elabftw.min.js"></script>
<!-- bootstrap JS -->
<script src="js/bootstrap/js/alert.js"></script>
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
        <?php echo _('Howdy,') . ' '; ?><a href='profile.php' title='<?php echo _('Profile'); ?>'><?php echo $_SESSION['username']; ?></a><br>
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
if (isset($_SESSION['errors']) && is_array($_SESSION['errors']) && count($_SESSION['errors']) > 0) {
    foreach ($_SESSION['errors'] as $msg) {
        display_message('error', $msg);
    }
    unset($_SESSION['errors']);
}

if (isset($_SESSION['infos']) && is_array($_SESSION['infos']) && count($_SESSION['infos']) > 0) {
    foreach ($_SESSION['infos'] as $msg) {
        display_message('info', $msg);
    }
    unset($_SESSION['infos']);
}
