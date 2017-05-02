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

$Users = new Users();

if (isset($_SESSION['auth'])) {
    // todolist
    $Todolist = new Todolist($_SESSION['userid']);
    $Users = new Users($_SESSION['userid']);
    $todoItems = $Todolist->readAll();

    echo $twig->render('todolist.html', array(
        'Users' => $Users,
        'todoItems' => $todoItems
    ));
}

// show some stats about generation time and number of SQL queries
$pdo = Db::getConnection();
$sqlNb = $pdo->getNumberOfQueries();
$generationTime = round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 5);

echo $twig->render('footer.html', array(
    'SESSION' => $_SESSION,
    'Users' => $Users,
    'sqlNb' => $sqlNb,
    'generationTime' => ' ' . $generationTime . ' '
));
