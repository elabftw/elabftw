<?php
/**
 * admin.php
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
 * Administration panel of a team
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Admin panel');
    require_once 'app/head.inc.php';

    if (!$_SESSION['is_admin']) {
        throw new Exception(Tools::error(true));
    }

    $FormKey = new FormKey();
    $Config = new Config();
    $Users = new Users($_SESSION['userid'], $Config);

    $StatusView = new StatusView(new Status($Users));
    $ItemsTypesView = new ItemsTypesView(new ItemsTypes($Users));
    $TeamGroups = new TeamGroups($_SESSION['team_id']);
    $Auth = new Auth();
    $Templates = new Templates($Users);
    $Teams = new Teams($_SESSION['team_id']);

    $itemsTypesArr = $ItemsTypesView->ItemsTypes->readAll();
    $teamConfigArr = $Teams->read();
    $teamGroupsArr = $TeamGroups->readAll();
    echo Tools::printArr($teamGroupsArr);
    $templatesArr = $Templates->readCommon();
    $usersArr = $Users->readAllFromTeam($_SESSION['team_id']);

    // VALIDATE USERS BLOCK
    // only show the frame if there is some users to validate and there is an email config
    $unvalidatedUsersArr = $Users->readAllFromTeam($_SESSION['team_id'], 0);

    if (count($unvalidatedUsersArr) != 0 && $Config->configArr['mail_from'] != 'notconfigured@example.com') {
        $message = _('There are users waiting for validation of their account:');
        $message .= "<form method='post' action='app/controllers/UsersController.php'>";
        $message .= "<input type='hidden' name='usersValidate' value='true' />";
        $message .= $FormKey->getFormkey();
        $message .= "<ul>";
        foreach ($unvalidatedUsersArr as $user) {
            $message .= "<li><label>
                <input type='checkbox' name='usersValidateIdArr[]'
                value='".$user['userid'] . "'> " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")
                </label></li>";
        }
        $message .= "</ul><div class='submitButtonDiv'>
        <button class='button' type='submit'>". _('Validate') . "</button></div>";
        echo Tools::displayMessage($message, 'ko');
        echo "</form>";
    }
    // END VALIDATE USERS BLOCK

    echo $twig->render('admin.html', array(
        'Auth' => $Auth,
        'FormKey' => $FormKey,
        'itemsTypesArr' => $itemsTypesArr,
        'ItemsTypesView' => $ItemsTypesView,
        'StatusView' => $StatusView,
        'session' => $_SESSION,
        'teamConfigArr' => $teamConfigArr,
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $templatesArr,
        'Users' => $Users,
        'usersArr' => $usersArr
    ));
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
