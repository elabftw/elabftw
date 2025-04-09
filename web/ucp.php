<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Auth\Local;
use Elabftw\Enums\Classification;
use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings for user
 */
require_once 'app/init.inc.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

try {
    $ApiKeys = new ApiKeys($App->Users);
    $apiKeysArr = $ApiKeys->readAll();

    $Teams = new Teams($App->Users);
    $TeamGroups = new TeamGroups($App->Users);
    $TeamTags = new TeamTags($App->Users);

    $Category = new ExperimentsCategories($Teams);
    $Status = new ExperimentsStatus($Teams);
    $entityData = array();
    $changelogData = array();
    $metadataGroups = array();

    // TEAM GROUPS
    $PermissionsHelper = new PermissionsHelper();

    // the items categoryArr for add link input
    $ItemsTypes = new ItemsTypes($App->Users);
    $itemsCategoryArr = $ItemsTypes->readAll();

    // Notifications
    $notificationsSettings = array(
        array(
            'designation' => _('New comment notification'),
            'setting' => 'notif_comment_created',
        ),
        array(
            'designation' => _('Step deadline'),
            'setting' => 'notif_step_deadline',
        ),
    );

    if ($App->Users->isAdmin) {
        $notificationsSettings[] =
            array(
                'designation' => _('New user created'),
                'setting' => 'notif_user_created',
            );
        $notificationsSettings[] =
            array(
                'designation' => _('New user need validation'),
                'setting' => 'notif_user_need_validation',
            );
        $notificationsSettings[] =
            array(
                'designation' => _('Booking event cancelled'),
                'setting' => 'notif_event_deleted',
            );
    }

    $showMfa = !Local::isMfaEnforced(
        $App->Users->userData['userid'],
        (int) $App->Config->configArr['enforce_mfa'],
    );

    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);

    $template = 'ucp.html';
    $renderArr = array(
        'apiKeysArr' => $apiKeysArr,
        'categoryArr' => $Category->readAll(),
        'changes' => $changelogData,
        'classificationArr' => Classification::getAssociativeArray(),
        'entityData' => $entityData,
        'itemsCategoryArr' => $itemsCategoryArr,
        'teamsArr' => $Teams->readAll(),
        'metadataGroups' => $metadataGroups,
        'scopedTeamgroupsArr' => $TeamGroups->readScopedTeamgroups(),
        'notificationsSettings' => $notificationsSettings,
        'pageTitle' => _('Settings'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'statusArr' => $Status->readAll(),
        'teamTagsArr' => $TeamTags->readAll(),
        'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
        'showMFA' => $showMfa,
        'usersArr' => $App->Users->readAllActiveFromTeam(),
    );
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
}
$Response->setContent($App->render($template, $renderArr));
$Response->send();
