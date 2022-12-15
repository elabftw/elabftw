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
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\FilterableColumn;
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

    // all the users from the current team
    protected array $allTeamUsersArr = array();

    public function __construct(protected App $App, protected AbstractEntity $Entity)
    {
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $this->visibilityArr = $TeamGroups->getVisibilityList();
        $this->teamGroupsFromUser = $TeamGroups->readGroupsFromUser();
        // only take active users
        $this->allTeamUsersArr = array_filter($this->App->Users->readAllFromTeam(), function ($u) {
            return $u['archived'] === 0;
        });
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
            $this->Entity->addFilter(FilterableColumn::Canread->value, 'public');
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
            'allTeamUsersArr' => $this->allTeamUsersArr,
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
        $this->Entity->setId($this->App->Request->query->getInt('id'));

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

        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'allTeamUsersArr' => $this->allTeamUsersArr,
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'itemsCategoryArr' => $itemsCategoryArr,
            'mode' => 'view',
            'revNum' => $Revisions->readCount(),
            'templatesArr' => $this->templatesArr,
            'timestamperFullname' => $this->Entity->getTimestamperFullname(),
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
            'allTeamUsersArr' => $this->allTeamUsersArr,
            'Entity' => $this->Entity,
            'entityData' => $this->Entity->entityData,
            'categoryArr' => $this->categoryArr,
            'deletableXp' => $this->getDeletableXp(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'lastModifierFullname' => $lastModifierFullname,
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'mode' => 'edit',
            'myTeamsArr' => $Teams->readMyTeams(),
            'myTeamgroupsArr' => $this->teamGroupsFromUser,
            'revNum' => $Revisions->readCount(),
            'templatesArr' => $this->templatesArr,
            'usersArr' => $this->Entity->Users->readAllFromTeam(),
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
}
