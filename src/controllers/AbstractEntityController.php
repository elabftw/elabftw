<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\Classification;
use Elabftw\Enums\Currency;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Changelog;
use Elabftw\Models\FavTags;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\RequestActions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Templates;
use Elabftw\Models\UserRequestActions;
use Elabftw\Models\Users;
use Elabftw\Services\AccessKeyHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;

use const ARRAY_FILTER_USE_KEY;

/**
 * For displaying an entity in show, view or edit mode
 */
abstract class AbstractEntityController implements ControllerInterface
{
    protected array $categoryArr = array();

    protected array $statusArr = array();

    protected array $visibilityArr = array();

    protected array $classificationArr = array();

    protected array $meaningArr = array();

    protected array $requestableActionArr = array();

    protected array $currencyArr = array();

    protected array $templatesArr = array();

    protected array $scopedTeamgroupsArr = array();

    public function __construct(protected App $App, protected AbstractEntity $Entity)
    {
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $PermissionsHelper = new PermissionsHelper();
        $this->visibilityArr = $PermissionsHelper->getAssociativeArray();
        $this->classificationArr = Classification::getAssociativeArray();
        $this->meaningArr = Meaning::getAssociativeArray();
        // exclude exclusive edit mode removal action
        $this->requestableActionArr = array_filter(
            RequestableAction::getAssociativeArray(),
            fn(int $key): bool => $key !== RequestableAction::RemoveExclusiveEditMode->value,
            ARRAY_FILTER_USE_KEY,
        );
        $this->currencyArr = Currency::getAssociativeArray();
        $this->scopedTeamgroupsArr = $TeamGroups->readScopedTeamgroups();
        $Templates = new Templates($this->Entity->Users);
        $this->templatesArr = $Templates->Pins->readAllSimple();
        if ($App->Request->query->has('archived') && $Entity instanceof AbstractConcreteEntity) {
            $Entity->Uploads->includeArchived = true;
        }

    }

    public function getResponse(): Response
    {
        return match ($this->App->Request->query->getAlpha('mode')) {
            'view' => $this->view(),
            'edit' => $this->edit(),
            'changelog' => $this->changelog(),
            default => $this->show(),
        };
    }

    /**
     * Show mode (several items displayed). Default view.
     */
    public function show(bool $isSearchPage = false): Response
    {
        // create the DisplayParams object from the query
        $DisplayParams = new DisplayParams($this->App->Users, $this->App->Request, $this->Entity->entityType);
        // used to get all tags for top page tag filter
        $TeamTags = new TeamTags($this->App->Users, $this->App->Users->userData['team']);

        // only show public to anon
        if ($this->App->Session->get('is_anon')) {
            $this->Entity->isAnon = true;
        }

        // must be before the call to getItemsArr
        if ($this->App->Users->userData['always_show_owned'] === 1) {
            $this->Entity->alwaysShowOwned = true;
        }

        $itemsArr = $this->getItemsArr();
        // if there is only one result, redirect to the entry directly
        if ($isSearchPage && count($itemsArr) === 1) {
            return new RedirectResponse(sprintf(
                '%s?mode=view&id=%d',
                $this->Entity->entityType->toPage(),
                $itemsArr[0]['id']
            ));
        }

        // get tags separately
        $tagsArr = array();
        if (!empty($itemsArr)) {
            $tagsArr = $this->Entity->getTags($itemsArr);
        }

        // store the query parameters in the Session
        $this->App->Session->set('lastquery', $this->App->Request->getQueryString());

        // FAVTAGS
        $FavTags = new FavTags($this->App->Users);
        $favTagsArr = $FavTags->readAll();

        // the items categoryArr for add link input
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $itemsCategoryArr = $ItemsTypes->readAll();

        $template = 'show.html';
        $UserRequestActions = new UserRequestActions($this->App->Users);

        $renderArr = array(
            'DisplayParams' => $DisplayParams,
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'statusArr' => $this->statusArr,
            'itemsCategoryArr' => $itemsCategoryArr,
            'favTagsArr' => $favTagsArr,
            'pinnedArr' => $this->Entity->Pins->readAll(),
            'itemsArr' => $itemsArr,
            'requestActionsArr' => $UserRequestActions->readAllFull(),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            // generate light show page
            'searchPage' => $isSearchPage,
            'tagsArr' => $tagsArr,
            // get all the tags for the top search bar
            'tagsArrForSelect' => $TeamTags->readFull(),
            'templatesArr' => $this->templatesArr,
            'usersArr' => $this->App->Users->readAllActiveFromTeam(),
            'visibilityArr' => $this->visibilityArr,
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }

    /**
     * Get the items
     */
    protected function getItemsArr(): array
    {
        return $this->Entity->readShow(new DisplayParams($this->App->Users, $this->App->Request, $this->Entity->entityType));
    }

    /**
     * View mode (one item displayed)
     */
    protected function view(): Response
    {
        // by default the id is taken from the URL
        $id = $this->App->Request->query->getInt('id');
        // but if we have an access_key we might be able to bypass read permissions
        if ($this->App->Request->query->has('access_key') && $this->App->Request->query->get('access_key') !== ($this->Entity->entityData['access_key'] ?? '')) {
            // for that we fetch the id not from the id param but from the access_key, so we will get a valid id that corresponds to an entity
            // with this access_key
            $id = (new AccessKeyHelper($this->Entity))->getIdFromAccessKey($this->App->Request->query->getString('access_key'));
            if ($id > 0) {
                $this->Entity->bypassReadPermission = true;
            }
        }
        $this->Entity->setId($id);

        // the items categoryArr for add link input
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $itemsCategoryArr = $ItemsTypes->readAll();

        $Teams = new Teams($this->Entity->Users);
        $RequestActions = new RequestActions($this->App->Users, $this->Entity);
        $ProcurementRequests = new ProcurementRequests($Teams);

        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'classificationArr' => $this->classificationArr,
            'currencyArr' => $this->currencyArr,
            'Entity' => $this->Entity,
            // Do we display the main body of a concrete entity? Default is true
            'displayMainText' => (new Metadata($this->Entity->entityData['metadata']))->getDisplayMainText(),
            'entityProcurementRequestsArr' => $ProcurementRequests->readActiveForEntity($this->Entity->id ?? 0),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'mode' => 'view',
            'hideTitle' => true,
            'teamsArr' => $Teams->readAll(),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            'templatesArr' => $this->templatesArr,
            ...$this->Entity instanceof AbstractConcreteEntity
                    ? array('timestamperFullname' => $this->Entity->getTimestamperFullname())
                    : array(),
            'lockerFullname' => $this->Entity->getLockerFullname(),
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'usersArr' => $this->App->Users->readAllActiveFromTeam(),
            'visibilityArr' => $this->visibilityArr,
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render('view.html', $renderArr));

        return $Response;
    }

    /**
     * Edit mode
     */
    protected function edit(): Response
    {
        $this->Entity->setId($this->App->Request->query->getInt('id'));
        // check permissions
        $this->Entity->canOrExplode('write');
        // a locked entity cannot be edited
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('This item is locked. You cannot edit it!'));
        }

        // exclusive edit mode
        $redirectResponse = $this->Entity->ExclusiveEditMode->gatekeeper();
        if ($redirectResponse instanceof RedirectResponse) {
            return ($redirectResponse);
        }

        // last modifier name
        $lastModifierFullname = '';
        if ($this->Entity->entityData['lastchangeby'] !== null) {
            $lastModifier = new Users($this->Entity->entityData['lastchangeby']);
            $lastModifierFullname = $lastModifier->userData['fullname'];
        }

        // the items categoryArr for add link input
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $itemsCategoryArr = $ItemsTypes->readAll();

        $Teams = new Teams($this->Entity->Users);
        $TeamTags = new TeamTags($this->App->Users);

        $RequestActions = new RequestActions($this->App->Users, $this->Entity);
        $ProcurementRequests = new ProcurementRequests($Teams);

        $Metadata = new Metadata($this->Entity->entityData['metadata']);
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'classificationArr' => $this->classificationArr,
            'currencyArr' => $this->currencyArr,
            'Entity' => $this->Entity,
            'entityData' => $this->Entity->entityData,
            'entityProcurementRequestsArr' => $ProcurementRequests->readActiveForEntity($this->Entity->id ?? 0),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            // Do we display the main body of a concrete entity? Default is true
            'displayMainText' => $Metadata->getDisplayMainText(),
            'hideTitle' => true,
            'itemsCategoryArr' => $itemsCategoryArr,
            'lastModifierFullname' => $lastModifierFullname,
            'metadataGroups' => $Metadata->getGroups(),
            'mode' => 'edit',
            'statusArr' => $this->statusArr,
            'teamsArr' => $Teams->readAll(),
            'teamTagsArr' => $TeamTags->readAll(),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'templatesArr' => $this->templatesArr,
            'usersArr' => $this->App->Users->readAllActiveFromTeam(),
            'visibilityArr' => $this->visibilityArr,
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render('edit.html', $renderArr));
        return $Response;
    }

    protected function changelog(): Response
    {
        $this->Entity->setId($this->App->Request->query->getInt('id'));
        // check permissions
        $this->Entity->canOrExplode('read');

        $Changelog = new Changelog($this->Entity);

        $renderArr = array(
            'changes' => $Changelog->readAll(),
            'Entity' => $this->Entity,
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render('changelog.html', $renderArr));
        return $Response;
    }
}
