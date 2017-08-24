<?php
/**
 * app/footer.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

if ($Session->has('auth')) {
    // todolist
    $Todolist = new Todolist($Session->get('userid'));
    $todoItems = $Todolist->readAll();

    echo $Twig->render('todolist.html', array(
        'Users' => $Users,
        'todoItems' => $todoItems
    ));
} else {
    $Users = null;
}

// show some stats about generation time and number of SQL queries
$pdo = Db::getConnection();
$sqlNb = $pdo->getNumberOfQueries();
$generationTime = round((microtime(true) - $Request->server->get("REQUEST_TIME_FLOAT")), 5);

$memoryUsage = Tools::formatBytes(memory_get_usage()) . ' (' . memory_get_usage() . ')';

echo $Twig->render('footer.html', array(
    'Session' => $Session,
    'Users' => $Users,
    'sqlNb' => $sqlNb,
    'generationTime' => $generationTime,
    'memoryUsage' => $memoryUsage,
    'debug' => $Update->Config->configArr['debug']
));
