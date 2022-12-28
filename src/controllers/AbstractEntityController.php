<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\Metadata;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Changelog;
use Elabftw\Models\Experiments;
use Elabftw\Models\FavTags;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Revisions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Elabftw\Services\AccessKeyHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * For displaying an entity in show, view or edit mode
 */
abstract class AbstractEntityController implements ControllerInterface
{
    protected array $categoryArr = array();

    protected array $visibilityArr = array();

    protected array $templatesArr = array();

    protected array $teamGroupsFromUser = array();

    public function __construct(protected App $App, protected AbstractEntity $Entity)
    {
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $PermissionsHelper = new PermissionsHelper();
        $this->visibilityArr = $PermissionsHelper->getAssociativeArray();
        $this->teamGroupsFromUser = $TeamGroups->readGroupsFromUser();
        // items don't need to show the templates in create new menu, so save a sql call here
        if ($this->Entity instanceof Experiments) {
            $Templates = new Templates($this->Entity->Users);
            $this->templatesArr = $Templates->Pins->readAllSimple();
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
        $DisplayParams = new DisplayParams($this->App->Users, $this->App->Request);
        // used to get all tags for top page tag filter
        $TeamTags = new TeamTags($this->App->Users, $this->App->Users->userData['team']);

        // TAG FILTER
        if (!empty(($this->App->Request->query->all('tags'))[0])) {
            // get all the ids with that tag
            $tagsFromGet = $this->App->Request->query->all('tags');
            $tagsFromGet = array_map(function ($t) {
                return (string) $t;
            }, $tagsFromGet);
            $ids = $this->Entity->Tags->getIdFromTags($tagsFromGet);
            $this->Entity->idFilter = Tools::getIdFilterSql($ids);
            $DisplayParams->searchType = 'tags';
        }

        // only show public to anon
        if ($this->App->Session->get('is_anon')) {
            $this->Entity->addFilter(FilterableColumn::Canread->value, BasePermissions::Full->value);
        }

        $itemsArr = $this->getItemsArr();
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

        $renderArr = array(
            'DisplayParams' => $DisplayParams,
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'deletableXp' => $this->getDeletableXp(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'favTagsArr' => $favTagsArr,
            'pinnedArr' => $this->Entity->Pins->readAll(),
            'itemsArr' => $itemsArr,
            // generate light show page
            'searchPage' => $isSearchPage,
            'searchType' => $isSearchPage ? 'something' : $DisplayParams->searchType,
            'tagsArr' => $tagsArr,
            // get all the tags for the top search bar
            'tagsArrForSelect' => $TeamTags->readAll(),
            'teamGroupsFromUser' => $this->teamGroupsFromUser,
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
    abstract protected function getItemsArr(): array;

    /**
     * View mode (one item displayed)
     */
    protected function view(): Response
    {
        // by default the id is taken from the URL
        $id = $this->App->Request->query->getInt('id');
        // but if we have an access_key we might be able to bypass read permissions
        if ($this->App->Request->query->has('access_key') && $this->App->Request->query->get('access_key') !== $this->Entity->entityData['access_key']) {
            // for that we fetch the id not from the id param but from the access_key, so we will get a valid id that corresponds to an entity
            // with this access_key
            $id = (new AccessKeyHelper($this->Entity))->getIdFromAccessKey((string) $this->App->Request->query->get('access_key'));
            if ($id > 0) {
                $this->Entity->bypassReadPermission = true;
            }
        }
        $this->Entity->setId($id);

        // REVISIONS
        $Revisions = new Revisions(
            $this->Entity,
            (int) $this->App->Config->configArr['max_revisions'],
            (int) $this->App->Config->configArr['min_delta_revisions'],
            (int) $this->App->Config->configArr['min_days_revisions'],
        );

        // the items categoryArr for add link input
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $itemsCategoryArr = $ItemsTypes->readAll();

        $Teams = new Teams($this->Entity->Users);

        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'Entity' => $this->Entity,
            'displayMainText' => $this->displayMainText(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'mode' => 'view',
            'myTeamsArr' => $Teams->readMyTeams(),
            'myTeamgroupsArr' => $this->teamGroupsFromUser,
            'revNum' => $Revisions->readCount(),
            'templatesArr' => $this->templatesArr,
            'timestamperFullname' => $this->Entity->getTimestamperFullname(),
            'usersArr' => $this->App->Users->readAllActiveFromTeam(),
            'visibilityArr' => $this->visibilityArr,
        );

        // RELATED ITEMS AND EXPERIMENTS
        if ($this->Entity instanceof AbstractConcreteEntity) {
            $renderArr['relatedItemsArr'] = $this->Entity->ItemsLinks->readRelated();
            $renderArr['relatedExperimentsArr'] = $this->Entity->ExperimentsLinks->readRelated();
        }

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
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        // check permissions
        $this->Entity->canOrExplode('write');
        // a locked entity cannot be edited
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('This item is locked. You cannot edit it!'));
        }

        // last modifier name
        $lastModifierFullname = '';
        if ($this->Entity->entityData['lastchangeby'] !== null) {
            $lastModifier = new Users((int) $this->Entity->entityData['lastchangeby']);
            $lastModifierFullname = $lastModifier->userData['fullname'];
        }

        // the items categoryArr for add link input
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $itemsCategoryArr = $ItemsTypes->readAll();

        // REVISIONS
        $Revisions = new Revisions(
            $this->Entity,
            (int) $this->App->Config->configArr['max_revisions'],
            (int) $this->App->Config->configArr['min_delta_revisions'],
            (int) $this->App->Config->configArr['min_days_revisions'],
        );
        $Teams = new Teams($this->Entity->Users);

        $renderArr = array(
            'categoryArr' => $this->categoryArr,
            'deletableXp' => $this->getDeletableXp(),
            'Entity' => $this->Entity,
            'entityData' => $this->Entity->entityData,
            'displayMainText' => $this->displayMainText(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'lastModifierFullname' => $lastModifierFullname,
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'mode' => 'edit',
            'myTeamsArr' => $Teams->readMyTeams(),
            'myTeamgroupsArr' => $this->teamGroupsFromUser,
            'revNum' => $Revisions->readCount(),
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
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
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

    /**
     * Can we delete experiments? This is used to disable the Delete button in menu.
     */
    private function getDeletableXp(): bool
    {
        // get the config option from team setting
        $Teams = new Teams($this->App->Users);
        $deletableXp = (bool) $Teams->readOne()['deletable_xp'];
        // general config will override the team config only if it's more restrictive
        if ($this->App->Config->configArr['deletable_xp'] === '0') {
            $deletableXp = false;
        }
        // an admin is able to delete
        if ($this->App->Users->userData['is_admin']) {
            $deletableXp = true;
        }
        return $deletableXp;
    }

    /**
     * Do we display the main body of a concrete entity?
     * Get the information from the metadata: {"elabftw": {"display_main_text": false}}
     * Default is true
     */
    private function displayMainText(): bool
    {
        $displayMainText = true;

        $metadata = json_decode($this->Entity->entityData['metadata'] ?? '{}', true);

        if (array_key_exists(Metadata::Elabftw->value, $metadata)
            && array_key_exists(Metadata::DisplayMainText->value, $metadata[Metadata::Elabftw->value])
        ) {
            $displayMainText = $metadata[Metadata::Elabftw->value][Metadata::DisplayMainText->value] === false ? false : true;
        }

        return $displayMainText;
    }
}
