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


    // USER INFOS
    echo "<section class='box'>";
    echo "<img src='app/img/user.png' alt='user' /> <h4 style='display:inline'>" . _('Infos') . "</h4>";
    echo "<hr>";
    echo "<div>
        <p>" . $Users->userData['firstname'] . " " . $Users->userData['lastname'] . " (" . $Users->userData['email'] . ")</p>
        <p>". $count . " " . _('experiments done since') . " " . date("l jS \of F Y", $Users->userData['register_date'])
        ."<p><a href='ucp.php'>" . _('Go to user control panel') . "</a>";
    echo "<div id='api_div'><h4>" . _("API key") . ":</h4>";
    if (!is_null($Users->userData['api_key'])) {
        echo "<input value='" . $Users->userData['api_key'] . "'></input>";
    }
    echo "<button class='button' onClick='generateApiKey()'>" . _("Generate an API Key") . "</button></p>";

    echo "</div></div>";
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
