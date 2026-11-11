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
use Elabftw\Enums\AccessType;
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
use Elabftw\Models\Config;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\FavTags;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
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

use function sprintf;
use function array_column;
use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function explode;

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

    public function __construct(protected App $App, protected AbstractEntity $Entity)
    {
        $PermissionsHelper = new PermissionsHelper();
        $this->visibilityArr = $PermissionsHelper->getAssociativeArray();
        $this->classificationArr = Classification::getAssociativeArray();
        $this->meaningArr = Meaning::getAssociativeArray();
        $this->requestableActionArr = RequestableAction::getAssociativeArray();
        $this->currencyArr = Currency::getAssociativeArray();
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
            'pageTitle' => $this->getPageTitle(),
            'requestActionsArr' => $UserRequestActions->readAllFull(),
            'allTeamgroupsArr' => $this->readAllTeamgroups(),
            'teamsArr' => $this->App->Teams->readAllVisible(),
            // get all the tags for the top search bar
            'tagsArrForSelect' => $TeamTags->readAll(),
            'usersArr' => $this->getOwnerFilterUsers(),
            'visibilityArr' => $this->visibilityArr,
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }

    abstract protected function getPageTitle(): string;

    /**
     * Team members, plus the users from other teams that are set in the owner filter,
     * so the filter can display their name and not just their id
     */
    protected function getOwnerFilterUsers(): array
    {
        $usersArr = $this->App->Users->readAllFromTeam();
        $teamUserids = array_map('intval', array_column($usersArr, 'userid'));
        $ownerIds = array_map('intval', explode(',', $this->App->Request->query->getString('owner')));
        $otherTeamsIds = array_diff(array_filter($ownerIds, fn(int $id): bool => $id > 0), $teamUserids);
        if (empty($otherTeamsIds)) {
            return $usersArr;
        }
        $otherTeamsUsers = array_map(fn(array $user): array => array(
            'userid' => $user['userid'],
            'fullname' => $user['fullname'],
            'is_archived_in_current_team' => 0,
            'is_current_team' => 0,
        ), $this->App->Users->readFullnamesFromIds($otherTeamsIds));
        return array_merge($usersArr, $otherTeamsUsers);
    }

    // empty by default because only for items
    protected function getEntityProcurementRequestsArr(): array
    {
        return array();
    }

    /**
     * View mode (one item displayed)
     */
    protected function view(): Response
    {
        $RequestActions = new RequestActions($this->App->Users, $this->Entity);
        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'classificationArr' => $this->classificationArr,
            'currencyArr' => $this->currencyArr,
            'Entity' => $this->Entity,
            'entityProcurementRequestsArr' => $this->getEntityProcurementRequestsArr(),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            'pageTitle' => $this->getPageTitle(),
            'mode' => 'view',
            'hideTitle' => true,
            'teamsArr' => $this->App->Teams->readAllVisible(),
            'allTeamgroupsArr' => $this->readAllTeamgroups(),
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'storageUnitsArr' => new StorageUnits($this->App->Users, Config::getConfig()->configArr['inventory_require_edit_rights'] === '1')->readAllRecursive(),
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
            'entityProcurementRequestsArr' => $this->getEntityProcurementRequestsArr(),
            'entityRequestActionsArr' => $RequestActions->readAllFull(),
            'hideTitle' => true,
            'metadataGroups' => $Metadata->getGroups(),
            'mode' => 'edit',
            'pageTitle' => $this->getPageTitle(),
            'statusArr' => $this->statusArr,
            'teamsArr' => $this->App->Teams->readAllVisible(),
            'teamTagsArr' => $TeamTags->readAll($baseQueryParams),
            'allTeamgroupsArr' => $this->readAllTeamgroups(),
            'meaningArr' => $this->meaningArr,
            'requestableActionArr' => $this->requestableActionArr,
            'storageUnitsArr' => new StorageUnits($this->App->Users, Config::getConfig()->configArr['inventory_require_edit_rights'] === '1')->readAllRecursive(),
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

    // the permissions modal and the user groups filter list every user group the user can see
    protected function readAllTeamgroups(): array
    {
        return new TeamGroups($this->Entity->Users)->readAllEverything();
    }

    protected function changelog(): Response
    {
        // check permissions
        $this->Entity->canOrExplode(AccessType::Read);

        $renderArr = array(
            'changes' => $this->Entity->entityData['changelog'],
            'Entity' => $this->Entity,
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render('changelog.html', $renderArr));
        return $Response;
    }
}
