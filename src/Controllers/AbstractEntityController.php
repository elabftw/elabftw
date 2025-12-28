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
use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\Classification;
use Elabftw\Enums\Currency;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\Sort;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Changelog;
use Elabftw\Models\Config;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ExtraFieldsKeys;
use Elabftw\Models\FavTags;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\RequestActions;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Templates;
use Elabftw\Models\UserRequestActions;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\BaseQueryParams;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * For displaying an entity in show, view or edit mode
 * Preloads shared data like templates (experiments/items), statuses, team info.
 */
abstract class AbstractEntityController implements ControllerInterface
{
    // is the main category of current entity type, can be experimentsCategoryArr or itemsCategoryArr
    protected array $categoryArr = array();

    protected array $experimentsStatusArr = array();

    protected array $itemsStatusArr = array();

    protected array $statusArr = array();

    protected array $visibilityArr = array();

    protected array $classificationArr = array();

    protected array $meaningArr = array();

    protected array $requestableActionArr = array();

    protected array $currencyArr = array();

    protected array $scopedTeamgroupsArr = array();

    public function __construct(protected App $App, protected AbstractEntity $Entity)
    {
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $PermissionsHelper = new PermissionsHelper();
        $this->visibilityArr = $PermissionsHelper->getAssociativeArray();
        $this->classificationArr = Classification::getAssociativeArray();
        $this->meaningArr = Meaning::getAssociativeArray();
        $this->requestableActionArr = RequestableAction::getAssociativeArray();
        $this->currencyArr = Currency::getAssociativeArray();
        $this->scopedTeamgroupsArr = $TeamGroups->readScopedTeamgroups();
        $ExperimentsStatus = new ExperimentsStatus($App->Teams);
        $this->experimentsStatusArr = $ExperimentsStatus->readAll($ExperimentsStatus->getQueryParams(new InputBag(array('limit' => 9999))));
        $ItemsStatus = new ItemsStatus($this->App->Teams);
        $this->itemsStatusArr = $ItemsStatus->readAll($ItemsStatus->getQueryParams(new InputBag(array('limit' => 9999))));
    }

    #[Override]
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
    public function show(): Response
    {
        // used to get all tags for top page tag filter
        $TeamTags = new TeamTags($this->App->Users, $this->App->Users->userData['team']);
        $ExtraFieldsKeys = new ExtraFieldsKeys($this->App->Users, '', -1);

        // only show public to anon
        if ($this->App->Session->get('is_anon')) {
            $this->Entity->isAnon = true;
        }

        // must be before the call to readShow
        if (($this->App->Users->userData['always_show_owned'] ?? null) === 1) {
            $this->Entity->alwaysShowOwned = true;
        }

        // read all based on query parameters or user defaults
        $orderBy = Orderby::tryFrom($this->App->Users->userData['orderby']) ?? Orderby::Lastchange;
        $skipOrderPinned = $this->App->Request->query->getBoolean('skip_pinned');
        $DisplayParams = new DisplayParams(
            requester: $this->App->Users,
            entityType: $this->Entity->entityType,
            query: $this->App->Request->query,
            orderby: $orderBy,
            sort: Sort::tryFrom($this->App->Users->userData['sort']) ?? Sort::Desc,
            limit: $this->App->Users->userData['limit_nb'],
            skipOrderPinned: $skipOrderPinned,
        );
        $itemsArr = $this->Entity->readShow($DisplayParams);

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

        $template = 'show.html';
        $UserRequestActions = new UserRequestActions($this->App->Users);

        $renderArr = array(
            'DisplayParams' => $DisplayParams,
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'statusArr' => $this->statusArr,
            'favTagsArr' => $favTagsArr,
            'itemsArr' => $itemsArr,
            'pageTitle' => $this->getPageTitle(),
            'metakeyArrForSelect' => array_column($ExtraFieldsKeys->readAll(), 'extra_fields_key'),
            'requestActionsArr' => $UserRequestActions->readAllFull(),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            'tagsArr' => $tagsArr,
            // get all the tags for the top search bar
            'tagsArrForSelect' => $TeamTags->readAll(),
            'usersArr' => $this->App->Users->readAllFromTeam(),
            'visibilityArr' => $this->visibilityArr,
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }

    abstract protected function getPageTitle(): string;

    /**
     * View mode (one item displayed)
     */
    protected function view(): Response
    {
        $RequestActions = new RequestActions($this->App->Users, $this->Entity);
        $ProcurementRequests = new ProcurementRequests($this->App->Teams);

        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'classificationArr' => $this->classificationArr,
            'currencyArr' => $this->currencyArr,
            'Entity' => $this->Entity,
            'entityProcurementRequestsArr' => $ProcurementRequests->readActiveForEntity($this->Entity->id ?? 0),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            'pageTitle' => $this->getPageTitle(),
            'mode' => 'view',
            'hideTitle' => true,
            'teamsArr' => $this->App->Teams->readAllVisible(),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            'timestamperFullname' => $this->Entity->getTimestamperFullname(),
            'lockerFullname' => $this->Entity->getLockerFullname(),
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'storageUnitsArr' => new StorageUnits($this->App->Users, Config::getConfig()->configArr['inventory_require_edit_rights'] === '1')->readAllRecursive(),
            'surroundingBookers' => $this->Entity->getSurroundingBookers(),
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
        // redirect to view mode if we don't have edit access
        if ($this->Entity->isReadOnly) {
            if (!isset($this->Entity->id)) {
                throw new ResourceNotFoundException();
            }
            return new RedirectResponse(sprintf(
                '%s%sid=%d',
                $this->Entity->entityType->toPage(),
                '?mode=view&',
                $this->Entity->id,
            ), Response::HTTP_SEE_OTHER); // 303
        }
        // all entities are in exclusive edit mode as of march 2025. See #5568
        $this->Entity->ExclusiveEditMode->activate();

        $TeamTags = new TeamTags($this->App->Users);

        $RequestActions = new RequestActions($this->App->Users, $this->Entity);
        $ProcurementRequests = new ProcurementRequests($this->App->Teams);

        $Metadata = new Metadata($this->Entity->entityData['metadata']);
        $baseQueryParams = new BaseQueryParams($this->App->Request->query);
        // used in field builder modal, TODO we might want to make it dynamic loading later
        $Templates = new Templates($this->App->Users);
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $DisplayParamsTemplates = new DisplayParams($this->App->Users, EntityType::Templates);
        $DisplayParamsItemsTypes = new DisplayParams($this->App->Users, EntityType::ItemsTypes);
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'classificationArr' => $this->classificationArr,
            'currencyArr' => $this->currencyArr,
            'Entity' => $this->Entity,
            'entityProcurementRequestsArr' => $ProcurementRequests->readActiveForEntity($this->Entity->id ?? 0),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            'hideTitle' => true,
            'metadataGroups' => $Metadata->getGroups(),
            'mode' => 'edit',
            'pageTitle' => $this->getPageTitle(),
            'statusArr' => $this->statusArr,
            'teamsArr' => $this->App->Teams->readAllVisible(),
            'teamTagsArr' => $TeamTags->readAll($baseQueryParams),
            'scopedTeamgroupsArr' => $this->scopedTeamgroupsArr,
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'storageUnitsArr' => new StorageUnits($this->App->Users, Config::getConfig()->configArr['inventory_require_edit_rights'] === '1')->readAllRecursive(),
            'surroundingBookers' => $this->Entity->getSurroundingBookers(),
            'templatesArr' => $Templates->readAllSimple($DisplayParamsTemplates),
            'itemsTemplatesArr' => $ItemsTypes->readAllSimple($DisplayParamsItemsTypes),
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
