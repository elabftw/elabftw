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

/**
 * Display profile of current user
 *
 */
require_once 'inc/common.php';
$page_title = _('Profile');
$selected_menu = null;
require_once 'inc/head.php';

// SQL to get number of experiments
$sql = "SELECT COUNT(*) FROM experiments WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();

$count = $req->fetch();

// SQL for profile
$sql = "SELECT * FROM users WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();
$data = $req->fetch();

echo "<section class='box'>";
echo "<img src='img/user.png' alt='user' /> <h4>" . _('Infos') . "</h4>";
echo "<div class='center'>
    <p>".$data['firstname'] . " " . $data['lastname'] . " (" . $data['email'] . ")</p>
    <p>".$count[0] . " " . _('experiments done since') . " " . date("l jS \of F Y", $data['register_date'])
    ."<p><a href='ucp.php'>" . _('Go to user control panel') . "</a>";
echo "</div>";
echo "</section>";
require_once 'inc/statistics.php';
require_once 'inc/tagcloud.php';
require_once 'inc/footer.php';
