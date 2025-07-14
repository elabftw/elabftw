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
use Elabftw\Exceptions\AppException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\TeamTags;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings for user
 */
require_once 'app/init.inc.php';

$Response = new Response();
try {
    $Response->prepare($App->Request);

    $ApiKeys = new ApiKeys($App->Users);
    $apiKeysArr = $ApiKeys->readAll();

    $TeamGroups = new TeamGroups($App->Users);
    $TeamTags = new TeamTags($App->Users);

    $Category = new ExperimentsCategories($App->Teams);
    $Status = new ExperimentsStatus($App->Teams);
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
        'teamsArr' => $App->Teams->readAllVisible(),
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
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
