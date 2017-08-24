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

if ($Session->has('auth')) {
    $Teams = new Teams($Users->userData['team']);
    $teamConfigArr = $Teams->read();

    // to redirect to the right page
    if ($selectedMenu === 'Database') {
        $actionTarget = 'database.php';
    }

    $getQ = '';
    if ($Request->query->has('q')) {
        $getQ = $Request->query->filter('q', null, FILTER_SANITIZE_STRING);
    }
}

//header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-eval' https://www.google.com/; connect-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://ajax.googleapis.com/ https://www.google.com/; font-src 'self'; object-src 'self';");
echo $Twig->render('head.html', array(
    'Session' => $Session,
    'Users' => $Users,
    'pageTitle' => $pageTitle,
    'selectedMenu' => $selectedMenu,
    'actionTarget' => $actionTarget,
    'teamConfigArr' => $teamConfigArr
));

// INFO BOX
foreach ($Session->getFlashBag()->get('ok', array()) as $msg) {
        echo Tools::displayMessage($msg, 'ok');
}
foreach ($Session->getFlashBag()->get('ko', array()) as $msg) {
        echo Tools::displayMessage($msg, 'ko');
}
