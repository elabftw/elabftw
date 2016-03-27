<?php
/**
 * inc/functions.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

use \Exception;
use \PDO;
use \Swift_Mailer;
use \Swift_SmtpTransport;
use \Swift_MailTransport;
use \Swift_SendmailTransport;

/**
 * This file holds global functions available everywhere.
 *
 * @deprecated
 */

/**
 * Main function to search for something
 *
 * @param string $type Can be 'xp' or 'db'
 * @param string $query The thing to search
 * @param int $userid Userid is used for 'xp' only
 * @return false|array $results_arr Array of ID with the $query string inside
 */
function search_item($type, $query, $userid)
{
    global $pdo;
    // we make an array for the resulting ids
    $results_arr = array();
    if ($type === 'xp') {
        // search in title date and body
        $sql = "SELECT id FROM experiments
            WHERE userid = :userid AND";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        // put resulting ids in the results array
        while ($data = $req->fetch()) {
            $results_arr[] = $data['id'];
        }
        // now we search in tags, and append the found ids to our result array
        $sql = "SELECT item_id FROM experiments_tags WHERE userid = :userid AND tag LIKE '%$query%'";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        // now we search in file comments and filenames
        $sql = "SELECT item_id FROM uploads WHERE userid = :userid AND (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'experiment'";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        $req->closeCursor();

    } elseif ($type === 'db') {
        // search in title date and body
        $sql = "SELECT id FROM items
            WHERE (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%')";
        $req = $pdo->prepare($sql);
        $req->execute();
        // put resulting ids in the results array
        while ($data = $req->fetch()) {
            $results_arr[] = $data['id'];
        }
        $req->closeCursor();
        // now we search in tags, and append the found ids to our result array
        $sql = "SELECT item_id FROM items_tags WHERE tag LIKE '%$query%'";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $_SESSION['userid']
        ));
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
        // now we search in file comments and filenames
        $sql = "SELECT item_id FROM uploads WHERE (comment LIKE '%$query%' OR real_name LIKE '%$query%') AND type = 'database'";
        $req = $pdo->prepare($sql);
        $req->execute();
        while ($data = $req->fetch()) {
            $results_arr[] = $data['item_id'];
        }
            $req->closeCursor();
    }
    // filter out duplicate ids and reverse the order; XP should be sorted by date
    return array_reverse(array_unique($results_arr));
}

/**
 * Display the tags.
 *
 * @param int $item_id The ID of the item for which we want the tags
 * @param string $table The table can be experiments_tags or items_tags
 * @return null|false Will show the HTML for tags or false if there is no tags
 */
function show_tags($item_id, $table)
{
    global $pdo;
    $sql = "SELECT tag FROM $table WHERE item_id = $item_id";
    $req = $pdo->prepare($sql);
    $req->execute();

    $html = '';
    $tagcount = $req->rowCount();
    if ($tagcount > 0) {
        $html = "<span class='tags'><img src='img/tags.png' alt='tags' /> ";
        while ($tags = $req->fetch()) {
            if ($table === 'experiments_tags') {
                $html .= "<a href='experiments.php?mode=show&tag=" . urlencode(stripslashes($tags['tag'])) . "'>" . stripslashes($tags['tag']) . "</a> ";
            } else { // table is items_tags
                $html .= "<a href='database.php?mode=show&tag=" . urlencode(stripslashes($tags['tag'])) . "'>" . stripslashes($tags['tag']) . "</a> ";
            }
        }
        $html .= "</span>";
    }
    return $html;
}
/**
 * Validate POST variables containing login/validation data for the TSP;
 * Substitute missing values with empty strings and return as array
 *
 * @return array
 */
function processTimestampPost()
{
    $crypto = new CryptoWrapper();

    if (isset($_POST['stampprovider'])) {
        $stampprovider = filter_var($_POST['stampprovider'], FILTER_VALIDATE_URL);
    } else {
        $stampprovider = '';
    }
    if (isset($_POST['stampcert'])) {
        $cert_chain = filter_var($_POST['stampcert'], FILTER_SANITIZE_STRING);
        if (is_readable(realpath(ELAB_ROOT . $cert_chain)) || realpath($cert_chain)) {
            $stampcert = $cert_chain;
        } else {
            $stampcert = '';
        }
    } else {
        $stampcert = '';
    }
    if (isset($_POST['stampshare'])) {
        $stampshare = $_POST['stampshare'];
    } else {
        $stampshare = 0;
    }
    if (isset($_POST['stamplogin'])) {
        $stamplogin = filter_var($_POST['stamplogin'], FILTER_SANITIZE_STRING);
    } else {
        $stamplogin = '';
    }
    if (isset($_POST['stamppass'])) {
        try {
            $stamppass = $crypto->encrypt($_POST['stamppass']);
        } catch (Exception $e) {
            $stamppass = '';
        }
    } else {
        $stamppass = '';
    }

    return array('stampprovider' => $stampprovider,
                    'stampcert' => $stampcert,
                    'stampshare' => $stampshare,
                    'stamplogin' => $stamplogin,
                    'stamppass' => $stamppass);
}

/**
 * Show an experiment (in mode=show).
 *
 * @param int $id The ID of the experiment to show
 * @param string $display Can be 'compact' or 'default'
 * @return string|null HTML of the single experiment
 */
function showXP($id, $display = 'default')
{
    $experiments = new Experiments($_SESSION['userid'], $id);
    $experiment = $experiments->read();

    if ($display === 'compact') {
        // COMPACT MODE //
        echo "<section class='item_compact' style='border-left: 6px solid #" . $experiment['color'] . "'>";
        echo "<a href='experiments.php?mode=view&id=" . $experiment['id'] . "'>";
        echo "<span class='date date_compact'>" . Tools::formatDate($experiment['date']) . "</span> ";
        echo "<span style='padding-left:10px;'>";
        // show lock if item is locked on viewXP
        if ($experiment['locked']) {
            echo "<img src='img/lock-blue.png' alt='lock' title='Locked' />";
        }
        echo stripslashes($experiment['title']);
        echo "</a></span></section>";
    } else { // NOT COMPACT
        ?>
        <section class="item" style='border-left: 6px solid #<?php echo $experiment['color']; ?>'>
        <?php
        // we show the abstract of the experiment on mouse hover with the title attribute
        // we check if it is our experiment. It would be best to check if we have visibility rights on it
        // but atm there is no such function. So we limit this feature to experiments we own, for simplicity.
        if (is_owned_by_user($id, 'experiments', $_SESSION['userid'])) {
            $body_abstract = str_replace("'", "", substr(strip_tags($experiment['body']), 0, 100));
        } else {
            $body_abstract = '';
        }
        echo "<a title='" . $body_abstract . "' href='experiments.php?mode=view&id=" . $experiment['id'] . "'>";
        // show stamp if experiment is timestamped
        if ($experiment['timestamped']) {
            echo "<img class='align_right' src='img/stamp.png' alt='stamp' title='experiment timestamped' />";
        }
        echo "<p class='title'>";
        // show lock if item is locked on viewXP
        if ($experiment['locked']) {
            echo "<img style='padding-bottom:3px;' src='img/lock-blue.png' alt='lock' title='Locked' /> ";
        }
        // TITLE
        echo stripslashes($experiment['title']) . "</p></a>";
        // DATE
        echo "<span class='date'><img class='image' src='img/calendar.png' /> " . Tools::formatDate($experiment['date']) . "</span> ";
        // _('Tags')
        echo show_tags($id, 'experiments_tags');
        // show attached if there is a file attached
        if ($experiments->hasAttachment('experiments')) {
            echo "<img class='align_right' src='img/attached.png' alt='file attached' />";
        }
        echo "</section>";
    }
}

/**
 * Display the stars rating for a DB item.
 *
 * @deprecated
 * @param int $rating The number of stars to display
 * @return string HTML of the stars
 */
function show_stars($rating)
{
    $html = "<span class='align_right'>";

    $green = "<img src='img/star-green.png' alt='☻' />";
    $gray = "<img src='img/star-gray.png' alt='☺' />";

    $html .= str_repeat($green, $rating);
    $html .= str_repeat($gray, (5 - $rating));

    $html .= "</span>";

    return $html;
}

/**
 * Display a DB item (in mode=show).
 *
 * @deprecated
 * @param int $id The ID of the item to show
 * @param string $display Can be 'compact' or 'default'
 * @return string|null HTML of the single item
 */
function showDB($id, $display = 'default')
{
    $database = new Database($_SESSION['team_id'], $id);
    $item = $database->read();

    if ($display === 'compact') {
        // COMPACT MODE //
        ?>
            <section class='item_compact' style='border-left: 6px solid #<?php echo $item['bgcolor']; ?>'>
            <a href='database.php?mode=view&id=<?php $id; ?>'>
            <span class='date date_compact'><?php echo $item['date']; ?></span>
            <h4 style='padding-left:10px;border-right:1px dotted #ccd;color:#<?php echo $item['bgcolor']; ?>'><?php echo $item['name']; ?> </h4>
            <span style='margin-left:7px'><?php echo stripslashes($item['title']); ?></span>
        <?php
        // STAR RATING read only
        echo show_stars($item['rating']);
        echo "</a></section>";

    } else { // NOT COMPACT

        echo "<section class='item' style='border-left: 6px solid #" . $item['bgcolor'] . "'>";
        echo "<a href='database.php?mode=view&id=" . $id . "'>";
        // show attached if there is a file attached
        if ($database->hasAttachment('items')) {
            echo "<img style='clear:both' class='align_right' src='img/attached.png' alt='file attached' />";
        }
        // STARS
        echo show_stars($item['rating']);
        echo "<p class='title'>";
        // show lock if item is locked on viewDB
        if ($item['locked'] == 1) {
            echo "<img style='padding-bottom:3px;' src='img/lock-blue.png' alt='lock' />";
        }
        // TITLE
        echo stripslashes($item['title']) . "</p></a>";
        // ITEM TYPE
        echo "<span style='text-transform:uppercase;font-size:80%;padding-left:20px;color:#" . $item['bgcolor'] . "'>" . $item['name'] . " </span>";
        // DATE
        echo "<span class='date' style='padding:0 5px;'><img class='image' src='img/calendar.png' /> " . Tools::formatDate($item['date']) . "</span> ";
        // TAGS
        echo show_tags($id, 'items_tags');
        echo "</section>";
    }
}

/**
 * Sanitize title with a filter_var and remove the line breaks.
 *
 * @param string $input The title to sanitize
 * @return string Will return empty string if there is no input.
 */
function check_title($input)
{
    // Check TITLE, what else ?
    if ((isset($input)) && (!empty($input))) {
        $title = filter_var($input, FILTER_SANITIZE_STRING);
        // remove linebreak to avoid problem in javascript link list generation on editXP
        return str_replace(array("\r\n", "\n", "\r"), ' ', $title);
    } else {
        return 'Untitled';
    }
}

/**
 * Check visibility for an experiment.
 *
 * @param string $input The visibility
 * @return string Will return team if the visibility is wrong
 */
function check_visibility($input)
{
    $valid_visibility = array(
        'public',
        'organization',
        'team',
        'user');

    if (in_array($input, $valid_visibility) || Tools::checkId($input)) {
        return $input;
    }
    // default is team
    return 'team';
}

/**
 * For displaying messages using jquery ui highlight/error messages
 *
 * @param string $type Can be 'ok', 'ko' or 'warning', with or without _nocross
 * @param string $message The message to display
 * @return boolean Will echo the HTML of the message
 */
function display_message($type, $message)
{
    if ($type === 'ok') {

        echo "<div class='alert alert-success'><span class='glyphicon glyphicon-ok-circle' aria-hidden='true'></span><a href='#' class='close' data-dismiss='alert'>&times</a> $message</div>";

    } elseif ($type === 'ok_nocross') {
        echo "<div class='alert alert-success'><span class='glyphicon glyphicon-info-sign' aria-hidden='true'></span> $message</div>";

    } elseif ($type === 'ko') {

        echo "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span><a href='#' class='close' data-dismiss='alert'>&times</a> $message</div>";

    } elseif ($type === 'ko_nocross') {
        echo "<div class='alert alert-danger'><span class='glyphicon glyphicon-remove-circle' aria-hidden='true'></span> $message</div>";

    } elseif ($type === 'warning') {
        echo "<div class='alert alert-warning'><span class='glyphicon glyphicon-chevron-right' aria-hidden='true'></span><a href='#' class='close' data-dismiss='alert'>&times</a> $message</div>";
    }

    return false;
}

/**
 * To check if something is owned by a user before we add/delete/edit.
 * There is a check only for experiments and experiments templates.
 *
 * @param int $id ID of the item to check
 * @param string $table Can be 'experiments' or experiments_templates'
 * @param int $userid The ID of the user to test
 * @return bool Will return true if it is owned by user
 */
function is_owned_by_user($id, $table, $userid)
{
    global $pdo;
    // type can be experiments or experiments_templates
    $sql = "SELECT userid FROM $table WHERE id = $id";
    $req = $pdo->prepare($sql);
    $req->execute();
    $result = $req->fetchColumn();
    return $result === $userid;
}

/**
 * Return conf_value of asked conf_name or the whole config as an associative array.
 *
 * @param string|null $conf_name The configuration we want to read
 * @return string The config value
 */
function get_config($conf_name = null)
{
    global $pdo;
    $final = array();

    $sql = "SELECT * FROM config";
    $req = $pdo->prepare($sql);
    $req->execute();
    $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
    if ($conf_name !== null) {
        return $config[$conf_name][0];
    }
    // return all the things!
    foreach ($config as $name => $value) {
        $final[$name] = $value[0];
    }
    return $final;
}

/**
 * Return the config for the team, or just the value of the column asked
 *
 * @param string|null $column
 * @return string|string[]
 */
function get_team_config($column = null)
{
    global $pdo;

    // remove notice when not logged in
    if (isset($_SESSION['team_id'])) {
        $sql = "SELECT * FROM `teams` WHERE team_id = :team_id";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team_id' => $_SESSION['team_id']
        ));
        $team_config = $req->fetch();
        if (is_null($column)) {
            return $team_config;
        }
        return $team_config[$column];
    }
    return "";
}


/**
 * Insert a log entry in the logs table
 *
 * @param string $type The type of the log. Can be 'Error', 'Warning', 'Info'
 * @param string $body The content of the log
 * @param string $user
 * @return bool Will return true if the query is successfull
 */
function dblog($type, $user, $body)
{
    global $pdo;

    // no need to check the params are they come from the code
    $sql = "INSERT INTO logs (type, user, body) VALUES (:type, :user, :body)";
    $req = $pdo->prepare($sql);
    $req->bindParam(':type', $type);
    $req->bindParam(':user', $user);
    $req->bindParam(':body', $body);
    try {
        $req->execute();
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Display the end of page.
 * Only used in install/index.php
 *
 * @return string|null The HTML of the end of the page
 */
function custom_die()
{
    echo "
    </section>
    </body>
    </html>";
    die();
}

/**
 * Used in sysconfig.php to update config values
 *
 * @param array $array (conf_name => conf_value)
 * @return bool the return value of execute queries
 */
function update_config($array)
{
    global $pdo;
    $result = array();
    foreach ($array as $name => $value) {
        $sql = "UPDATE config SET conf_value = :value WHERE conf_name = :name";
        $req = $pdo->prepare($sql);
        $req->bindParam(':value', $value);
        $req->bindParam(':name', $name);
        $result[] = $req->execute();
    }
    return !in_array(0, $result);
}

/*
 * Functions to keep current order/filter selection in dropdown
 *
 * @param string value to check
 * @return string|null echo 'selected'
 */

function checkSelectOrder($val)
{
    if (isset($_GET['order']) && $_GET['order'] === $val) {
        return " selected";
    }
}

function checkSelectSort($val)
{
    if (isset($_GET['sort']) && $_GET['sort'] === $val) {
        return " selected";
    }
}

function checkSelectFilter($val)
{
    if (isset($_GET['filter']) && $_GET['filter'] === $val) {
        return " selected";
    }
}

/*
 * Import the SQL structure
 *
 */
function import_sql_structure()
{
    global $pdo;

    $sqlfile = 'elabftw.sql';

    // temporary variable, used to store current query
    $queryline = '';
    // read in entire file
    $lines = file($sqlfile);
    // loop through each line
    foreach ($lines as $line) {
        // Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
        }

        // Add this line to the current segment
        $queryline .= $line;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            $pdo->query($queryline);
            // Reset temp variable to empty
            $queryline = '';
        }
    }
}

/*
 * Returns Swift_Mailer instance and chooses between sendmail and smtp
 * @return Swift_Mailer return Swift_Mailer instance
 */
function getMailer()
{
    // Choose mail transport method; either smtp or sendmail
    $mail_method = get_config('mail_method');

    $crypto = new CryptoWrapper();

    switch ($mail_method) {

        // Use SMTP Server
        case 'smtp':
            $transport = Swift_SmtpTransport::newInstance(
                get_config('smtp_address'),
                get_config('smtp_port'),
                get_config('smtp_encryption')
            )
            ->setUsername(get_config('smtp_username'))
            ->setPassword($crypto->decrypt(get_config('smtp_password')));
            break;

        // Use php mail function
        case 'php':
            $transport = Swift_MailTransport::newInstance();
            break;

        // Use locally installed MTA (aka sendmail); Default
        default:
            $transport = Swift_SendmailTransport::newInstance(get_config('sendmail_path') . ' -bs');
            break;
    }

    $mailer = Swift_Mailer::newInstance($transport);
    return $mailer;
}

/**
 * Get the time difference between start of page and now.
 *
 * @return array with time and unit
 */
function get_total_time()
{
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    $total_time = round(($time - $_SERVER["REQUEST_TIME_FLOAT"]), 4);
    $unit = _('seconds');
    if ($total_time < 0.01) {
        $total_time = $total_time * 1000;
        $unit = _('milliseconds');
    }
    return array(
        'time' => $total_time,
        'unit' => $unit);
}

/**
 * Display the tags
 *
 * @return string HTML
 */
function displayTags($type, $id)
{
    global $pdo;
    $sql = "SELECT id, tag FROM " . $type . "_tags WHERE item_id = :item_id";
    $tagreq = $pdo->prepare($sql);
    $tagreq->bindParam(':item_id', $id);
    $tagreq->execute();

    $html = "<img src='img/tags.png' class='bot5px' alt='tags' /><label for='addtaginput'>" . _('Tags') . "</label>";
    $html .= "<div class='tags'><span id='tags_div'>";
    while ($tags = $tagreq->fetch()) {
        $html .= "<span class='tag'><a onclick='delete_tag(" . $tags['id'] . "," . $id . ")'>" . stripslashes($tags['tag']) . "</a></span>";
    }
    $html .= "</span><input type='text' id='createTagInput' placeholder='" . _('Add a tag') . "' /></div>";

    return $html;
}

/**
 * Inject the script/css for chemdoodle
 *
 * @return string|null
 */
function addChemdoodle()
{
    if ($_SESSION['prefs']['chem_editor']) {
        $html = "<link rel='stylesheet' href='css/chemdoodle.css' type='text/css'>";
        $html .= "<script src='js/chemdoodle.js'></script>";
        $html .= "<script src='js/chemdoodle-uis.js'></script>";
        $html .= "<script>ChemDoodle.iChemLabs.useHTTPS();</script>";
        return $html;
    }
    return null;
}

/**
 * Generate a JS list of DB items to use for links or # autocomplete
 *
 * @param $format string ask if you want the default list for links, or the one for the mentions
 * @since 1.1.7 it adds the XP of user
 * @return string
 */
function getDbList($format = 'default')
{
    global $pdo;

    $link_list = "";
    $tinymce_list = "";
    $sql = "SELECT items_types.name,
    items.id AS itemid,
    items.* FROM items
    LEFT JOIN items_types
    ON items.type = items_types.id
    WHERE items.team = :team";
    $getalllinks = $pdo->prepare($sql);
    $getalllinks->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
    if ($getalllinks->execute()) {

        while ($link = $getalllinks->fetch()) {
            $link_type = $link['name'];
            // html_entity_decode is needed to convert the quotes
            // str_replace to remove ' because it messes everything up
            $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($link['title'], 0, 60), ENT_QUOTES));
            // remove also the % (see issue #62)
            $link_name = str_replace("%", "", $link_name);

            // now build the list in both formats
            $link_list .= "'" . $link['itemid'] . " - " . $link_type . " - " . $link_name . "',";
            $tinymce_list .= "{ name : \"<a href='database.php?mode=view&id=" . $link['itemid'] . "'>" . $link_name . "</a>\"},";
        }
    }

    if ($format === 'default') {
        return $link_list;
    } else {
        // complete the list with experiments (only for tinymce)
        // fix #191
        $sql = "SELECT id, title FROM experiments WHERE userid = :userid";
        $getalllinks = $pdo->prepare($sql);
        $getalllinks->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
        if ($getalllinks->execute()) {

            while ($link = $getalllinks->fetch()) {
                $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($link['title'], 0, 60), ENT_QUOTES));
                // remove also the % (see issue #62)
                $link_name = str_replace("%", "", $link_name);
                $tinymce_list .= "{ name : \"<a href='experiments.php?mode=view&id=" . $link['id'] . "'>" . $link_name . "</a>\"},";
            }
        }

        return $tinymce_list;
    }
}
