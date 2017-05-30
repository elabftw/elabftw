<?php
/**
 * inc/head.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

if (!isset($pageTitle)) {
    $pageTitle = "Lab manager";
}

if (!isset($selectedMenu)) {
    $selectedMenu = null;
}

$actionTarget = 'experiments.php';
$teamConfigArr = array();

if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    $Teams = new Teams($_SESSION['team_id']);
    $teamConfigArr = $Teams->read();

    // to redirect to the right page
    if ($selectedMenu === 'Database') {
        $actionTarget = 'database.php';
    }

    $getQ = '';
    if (isset($_GET['q'])) {
        $getQ = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
    }
}

//header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-eval' https://www.google.com/; connect-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://ajax.googleapis.com/ https://www.google.com/; font-src 'self'; object-src 'self';");
echo $twig->render('head.html', array(
    'session' => $_SESSION,
    'pageTitle' => $pageTitle,
    'selectedMenu' => $selectedMenu,
    'actionTarget' => $actionTarget,
    'teamConfigArr' => $teamConfigArr
));

// INFO BOX
if (isset($_SESSION['ko']) && is_array($_SESSION['ko']) && count($_SESSION['ko']) > 0) {
    foreach ($_SESSION['ko'] as $msg) {
        echo Tools::displayMessage($msg, 'ko');
    }
    unset($_SESSION['ko']);
}

if (isset($_SESSION['ok']) && is_array($_SESSION['ok']) && count($_SESSION['ok']) > 0) {
    foreach ($_SESSION['ok'] as $msg) {
        echo Tools::displayMessage($msg, 'ok');
    }
    unset($_SESSION['ok']);
}
