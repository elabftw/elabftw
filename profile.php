<?php
/**
 * profile.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Display profile of current user
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Profile');
    require_once 'app/head.inc.php';

    // get total number of experiments
    $Entity = new Experiments($Users);
    $Entity->setUseridFilter();
    $itemsArr = $Entity->read();
    $count = count($itemsArr);
    $UserStats = new UserStats($Users, $count);
    $TagCloud = new TagCloud($Users->userid);

    echo $twig->render('profile.html', array(
        'Users' => $Users,
        'UserStats' => $UserStats,
        'TagCloud' => $TagCloud,
        'count' => $count
    ));

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
} finally {
    require_once 'app/footer.inc.php';
}
