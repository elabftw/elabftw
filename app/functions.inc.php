<?php
/**
 * inc/functions.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

/**
 * This file holds global functions available everywhere.
 *
 * @deprecated
 */

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
    if (isset($_GET['cat']) && $_GET['cat'] === $val) {
        return " selected";
    }
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
 * Generate a JS list of DB items to use for links or # autocomplete
 *
 * @param $format string ask if you want the default list for links, or the one for the mentions
 * @since 1.1.7 it adds the XP of user
 * @return string
 *
function getDbList($format = 'default')
{
    $link_list = "";
    $tinymce_list = "";

    $Users = new Users($_SESSION['userid']);
    $Database = new Database($Users);
    $itemsArr = $Database->read();

    foreach ($itemsArr as $item) {

        // html_entity_decode is needed to convert the quotes
        // str_replace to remove ' because it messes everything up
        $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($item['title'], 0, 60), ENT_QUOTES));
        // remove also the % (see issue #62)
        $link_name = str_replace("%", "", $link_name);

        // now build the list in both formats
        $link_list .= "'" . $item['id'] . " - " . $item['category'] . " - " . $link_name . "',";
        $tinymce_list .= "{ name : \"<a href='database.php?mode=view&id=" . $item['id'] . "'>" . $link_name . "</a>\"},";
    }

    if ($format === 'default') {
        return $link_list;
    }

    // complete the list with experiments (only for tinymce)
    // fix #191
    $Experiments = new Experiments($Users);
    if ($format === 'mention-user') {
        $Experiments->setUseridFilter();
    }
    $expArr = $Experiments->read();

    foreach ($expArr as $exp) {

        $link_name = str_replace(array("'", "\""), "", html_entity_decode(substr($exp['title'], 0, 60), ENT_QUOTES));
        // remove also the % (see issue #62)
        $link_name = str_replace("%", "", $link_name);
        $tinymce_list .= "{ name : \"<a href='experiments.php?mode=view&id=" . $exp['id'] . "'>" . $link_name . "</a>\"},";
    }

    return $tinymce_list;
}
 */
