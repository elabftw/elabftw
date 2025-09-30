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
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\TeamTags;
use Elabftw\Services\DummyRemoteDirectory;
use Elabftw\Services\EairefRemoteDirectory;
use Elabftw\Services\Filter;
use Elabftw\Services\UsersHelper;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;

/**
 * Administration panel of a team
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
    if (!$App->Users->isAdmin) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    $ItemsTypes = new ItemsTypes($App->Users, Filter::intOrNull($Request->query->getInt('templateid')));
    $Status = new ExperimentsStatus($App->Teams);
    $ItemsStatus = new ItemsStatus($App->Teams);
    $TeamTags = new TeamTags($App->Users);
    $TeamGroups = new TeamGroups($App->Users);
    $PermissionsHelper = new PermissionsHelper();
    $teamStats = $App->Teams->getStats($App->Users->userData['team']);

    if ($App->Request->query->has('templateid')) {
        $ItemsTypes->setId($App->Request->query->getInt('templateid'));
        $ItemsTypes->canOrExplode('write');
        $ContainersLinks = LinksFactory::getContainersLinks($ItemsTypes);
        $ItemsTypes->entityData['containers'] = $ContainersLinks->readAll();
    }
    $statusArr = $Status->readAll($Status->getQueryParams(new InputBag(array('limit' => 9999))));
    $teamGroupsArr = $TeamGroups->readAll();
    $allTeamUsersArr = $App->Users->readAllFromTeam();
    // only the unvalidated ones
    $unvalidatedUsersArr = array_filter(
        $allTeamUsersArr,
        fn($u): bool => $u['validated'] === 0,
    );
    // Users search
    $usersArr = array();
    if ($App->Request->query->has('q')) {
        $usersArr = $App->Users->readFromQuery(
            $App->Request->query->getString('q'),
            $App->Request->query->getInt('team'),
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
        'allTeamUsersArr' => $allTeamUsersArr,
        'tagsArr' => $TeamTags->readAll(),
        'metadataGroups' => $metadataGroups,
        'allTeamgroupsArr' => $TeamGroups->readAllEverything(),
        'statusArr' => $statusArr,
        'itemsStatusArr' => $ItemsStatus->readAll(),
        'pageTitle' => _('Admin panel'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'teamGroupsArr' => $teamGroupsArr,
        'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
        'remoteDirectoryUsersArr' => $remoteDirectoryUsersArr,
        'scopedTeamgroupsArr' => $TeamGroups->readScopedTeamgroups(),
        'teamStats' => $teamStats,
        'teamsArr' => $App->Teams->readAllComplete(),
        'visibleTeamsArr' => $App->Teams->readAllVisible(),
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr,
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
