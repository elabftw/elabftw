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
require_once 'app/init.inc.php';
$page_title = _('Profile');
$selected_menu = null;
require_once 'app/head.inc.php';

try {
    $Experiments = new Experiments($_SESSION['team_id'], $_SESSION['userid']);
    $expArr = $Experiments->readAllFromUser();
    $count = count($expArr);

    $Users = new Users();
    $user = $Users->read($_SESSION['userid']);

    // USER INFOS
    echo "<section class='box'>";
    echo "<img src='img/user.png' alt='user' /> <h4 style='display:inline'>" . _('Infos') . "</h4>";
    echo "<hr>";
    echo "<div>
        <p>".$user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")</p>
        <p>". $count . " " . _('experiments done since') . " " . date("l jS \of F Y", $user['register_date'])
        ."<p><a href='ucp.php'>" . _('Go to user control panel') . "</a>";
    echo "</div>";
    echo "</section>";

    // STATUS STATS
    echo "<section class='box'>";
    if ($count === 0) {
        echo _('No statistics available yet.'); // fix division by zero
    } else {
        $UserStats = new UserStats($_SESSION['team_id'], $_SESSION['userid'], $count);
        echo $UserStats->show();
    }
    echo "</section>";

    // TAGCLOUD
    $TagCloud = new TagCloud($_SESSION['userid']);
    echo $TagCloud->show();

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
} finally {
    require_once 'app/footer.inc.php';
}
