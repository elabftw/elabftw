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
require_once 'app/init.inc.php';
$page_title = _('Profile');
$selected_menu = null;
require_once 'app/head.inc.php';

try {
    // get total number of experiments
    $Users = new Users($_SESSION['userid']);
    $Entity = new Experiments($Users);
    $Entity->setUseridFilter();
    $itemsArr = $Entity->read();
    $count = count($itemsArr);
    $UserStats = new UserStats($_SESSION['team_id'], $_SESSION['userid'], $count);
    $TagCloud = new TagCloud($_SESSION['userid']);

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
