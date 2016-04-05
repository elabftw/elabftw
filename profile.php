<?php
/**
 * profile.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Display profile of current user
 *
 */
require_once 'inc/common.php';
$page_title = _('Profile');
$selected_menu = null;
require_once 'inc/head.php';

try {
    $Experiments = new Experiments($_SESSION['userid']);
    $expArr = $Experiments->readAll();
    $count = count($expArr);

    $Users = new Users();
    $user = $Users->read($_SESSION['userid']);

    echo "<section class='box'>";
    echo "<img src='img/user.png' alt='user' class='bot5px' /> <h4 style='display:inline'>" . _('Infos') . "</h4>";
    echo "<div class='center'>
        <p>".$user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")</p>
        <p>". $count . " " . _('experiments done since') . " " . date("l jS \of F Y", $user['register_date'])
        ."<p><a href='ucp.php'>" . _('Go to user control panel') . "</a>";
    echo "</div>";
    echo "</section>";
    require_once 'inc/statistics.php';
    require_once 'inc/tagcloud.php';

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
