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

use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Services\DummyRemoteDirectory;
use Elabftw\Services\EairefRemoteDirectory;
use Elabftw\Services\UsersHelper;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;

/**
 * Administration panel of a team
 */
require_once 'app/init.inc.php';
$Response = new Response();
$Response->prepare($App->Request);

$template = 'error.html';
$renderArr = array();

try {
    if (!$App->Users->isAdmin) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    $ItemsTypes = new ItemsTypes($App->Users);
    $Teams = new Teams($App->Users, $App->Users->userData['team']);
    $Status = new ExperimentsStatus($Teams);
    $ItemsStatus = new ItemsStatus($Teams);
    $TeamTags = new TeamTags($App->Users);
    $TeamGroups = new TeamGroups($App->Users);
    $PermissionsHelper = new PermissionsHelper();
    $teamStats = $Teams->getStats($App->Users->userData['team']);

    $itemsCategoryArr = $ItemsTypes->readAll();
    $ExperimentsCategories = new ExperimentsCategories($Teams);
    $experimentsCategoriesArr = $ExperimentsCategories->readAll();
    if ($App->Request->query->has('templateid')) {
        $ItemsTypes->setId($App->Request->query->getInt('templateid'));
        $ItemsTypes->canOrExplode('write');
        $ItemsTypes->ExclusiveEditMode->enforceExclusiveModeBasedOnUserSetting();
        $ContainersLinks = LinksFactory::getContainersLinks($ItemsTypes);
        $ItemsTypes->entityData['containers'] = $ContainersLinks->readAll();
    }
    $statusArr = $Status->readAll();
    $teamGroupsArr = $TeamGroups->readAll();
    $teamsArr = $Teams->readAll();
    $allTeamUsersArr = $App->Users->readAllFromTeam();
    // only the unvalidated ones
    $unvalidatedUsersArr = array_filter(
        $allTeamUsersArr,
        fn($u): bool => $u['validated'] === 0,
    );
    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($App->Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readFromQuery(
            $App->Request->query->getString('q'),
            $App->Request->query->getInt('teamFilter'),
            $App->Request->query->getBoolean('includeArchived'),
            $App->Request->query->getBoolean('onlyAdmins'),
            $App->Request->query->getBoolean('onlyArchived'),
        );
        foreach ($usersArr as &$user) {
            $UsersHelper = new UsersHelper($user['userid']);
            $user['teams'] = $UsersHelper->getTeamsFromUserid();
        }
    }

    // Remote directory search
    $remoteDirectoryUsersArr = array();
    if ($App->Request->query->has('remote_dir_query')) {
        if ($App->Config->configArr['remote_dir_service'] === 'eairef') {
            $RemoteDirectory = new EairefRemoteDirectory(new Client(), $App->Config->configArr['remote_dir_config']);
        } else {
            $RemoteDirectory = new DummyRemoteDirectory(new Client(), $App->Config->configArr['remote_dir_config']);
        }
        $remoteDirectoryUsersArr = $RemoteDirectory->search($App->Request->query->getString('remote_dir_query'));
        if (empty($remoteDirectoryUsersArr)) {
            $App->Session->getFlashBag()->add('warning', _('No users found. Try another search.'));
        }
    }

    $metadataGroups = array();
    if (isset($ItemsTypes->entityData['metadata'])) {
        $metadataGroups = (new Metadata($ItemsTypes->entityData['metadata']))->getGroups();
    }
    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);

    $template = 'admin.html';
    $renderArr = array(
        'Entity' => $ItemsTypes,
        'allTeamUsersArr' => $allTeamUsersArr,
        'tagsArr' => $TeamTags->readAll(),
        'isSearching' => $isSearching,
        'itemsCategoryArr' => $itemsCategoryArr,
        'metadataGroups' => $metadataGroups,
        'allTeamgroupsArr' => $TeamGroups->readAllEverything(),
        'statusArr' => $statusArr,
        'experimentsCategoriesArr' => $experimentsCategoriesArr,
        'itemsStatusArr' => $ItemsStatus->readAll(),
        'pageTitle' => _('Admin panel'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'teamGroupsArr' => $teamGroupsArr,
        'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
        'remoteDirectoryUsersArr' => $remoteDirectoryUsersArr,
        'scopedTeamgroupsArr' => $TeamGroups->readScopedTeamgroups(),
        'storageUnitsArr' => (new StorageUnits($App->Users))->readAllRecursive(),
        'teamsArr' => $teamsArr,
        'teamStats' => $teamStats,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr,
    );
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $renderArr['error'] = Tools::error(true);
} catch (DatabaseErrorException | ImproperActionException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $renderArr['error'] = $e->getMessage();
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr['error'] = Tools::error();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
