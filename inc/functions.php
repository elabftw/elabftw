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
            throw new Exception('Cannot read provided certificate file.');
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
    if (isset($_POST['stamppass']) && !empty($_POST['stamppass'])) {
        $stamppass = $crypto->encrypt($_POST['stamppass']);
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
 * Inject the script/css for chemdoodle
 *
 * @return string|null
 */
function addChemdoodle()
{
    if (isset($_SESSION['prefs']['chem_editor']) && $_SESSION['prefs']['chem_editor']) {
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
    $link_list = "";
    $tinymce_list = "";

    $Database = new Database($_SESSION['team_id']);
    $itemsArr = $Database->readAll();

    foreach ($itemsArr as $item) {

        // html_entity_decode is needed to convert the quotes
        // str_replace to remove ' because it messes everything up
        $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($item['title'], 0, 60), ENT_QUOTES));
        // remove also the % (see issue #62)
        $link_name = str_replace("%", "", $link_name);

        // now build the list in both formats
        $link_list .= "'" . $item['itemid'] . " - " . $item['name'] . " - " . $link_name . "',";
        $tinymce_list .= "{ name : \"<a href='database.php?mode=view&id=" . $item['itemid'] . "'>" . $link_name . "</a>\"},";
    }

    if ($format === 'default') {
        return $link_list;
    }

    // complete the list with experiments (only for tinymce)
    // fix #191
    $Experiments = new Experiments($_SESSION['userid']);
    $expArr = $Experiments->readAll();

    foreach ($expArr as $exp) {

        $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($exp['title'], 0, 60), ENT_QUOTES));
        // remove also the % (see issue #62)
        $link_name = str_replace("%", "", $link_name);
        $tinymce_list .= "{ name : \"<a href='experiments.php?mode=view&id=" . $exp['id'] . "'>" . $link_name . "</a>\"},";
    }

    return $tinymce_list;
}
