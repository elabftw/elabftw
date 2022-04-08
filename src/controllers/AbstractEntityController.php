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
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Maps\Team;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\FavTags;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Revisions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Response;
use function trim;

/**
 * For experiments.php
 */
abstract class AbstractEntityController implements ControllerInterface
{
    protected Templates $Templates;

    protected array $categoryArr = array();

    /** @var AbstractEntity $Entity */
    protected $Entity;

    public function __construct(protected App $App, AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Templates = new Templates($entity->Users);
    }

    /**
     * Get the Response object from the Request
     */
    public function getResponse(): Response
    {
        switch ($this->App->Request->query->get('mode')) {
            case 'view':
                return $this->view();
            case 'edit':
                return $this->edit();
            default:
                return $this->show();
        }
    }

    /**
     * Show mode (several items displayed). Default view.
     */
    public function show(bool $isSearchPage = false): Response
    {
        // create the DisplayParams object from the query
        $DisplayParams = new DisplayParams();
        $DisplayParams->adjust($this->App);

        // VISIBILITY LIST
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        // CATEGORY FILTER
        if (Check::id((int) $this->App->Request->query->get('cat')) !== false) {
            $this->Entity->addFilter('categoryt.id', $this->App->Request->query->getDigits('cat'));
            $DisplayParams->searchType = 'category';
        }
        // OWNER (USERID) FILTER
        if ($this->App->Request->query->has('owner') && !$isSearchPage) {
            $owner = (int) $this->App->Request->query->get('owner');
            $this->Entity->addFilter('entity.userid', (string) $owner);
            $DisplayParams->searchType = 'user';
        }

        // TAG FILTER
        if (!empty(((array) $this->App->Request->query->all('tags'))[0])) {
            // get all the ids with that tag
            $tagsFromGet = (array) $this->App->Request->query->all('tags');
            $tagsFromGet = array_map(function ($t) {
                return (string) $t;
            }, $tagsFromGet);
            $ids = $this->Entity->Tags->getIdFromTags($tagsFromGet, (int) $this->App->Users->userData['team']);
            $trimmedFilter = Tools::getIdFilterSql($ids);
            // don't add it if it's empty (for instance we search in items for a tag that only exists on experiments)
            if ($trimmedFilter === ' AND ()') {
                $this->Entity->idFilter = ' AND entity.id = 0';
            } else {
                $this->Entity->idFilter = $trimmedFilter;
            }
            $DisplayParams->searchType = 'tags';
        }

        // only show public to anon
        if ($this->App->Session->get('is_anon')) {
            $this->Entity->addFilter('entity.canread', 'public');
        }

        // Quicksearch
        $extendedError = $this->prepareAdvancedSearchQuery($visibilityArr, $TeamGroups->readGroupsWithUsersFromUser());

        $itemsArr = $this->getItemsArr();
        // get tags separately
        $tagsArr = array();
        if (!empty($itemsArr)) {
            $tagsArr = $this->Entity->getTags($itemsArr);
        }
        // get all the tags for the top search bar
        $tagsArrForSelect = $this->Entity->Tags->readAll();

        // store the query parameters in the Session
        $this->App->Session->set('lastquery', $this->App->Request->query->all());

        // FAVTAGS
        $FavTags = new FavTags($this->App->Users);
        $favTagsArr = $FavTags->read(new ContentParams());

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
            'pinnedArr' => $this->Entity->Pins->getPinned(),
            'itemsArr' => $itemsArr,
            // generate light show page
            'searchPage' => $isSearchPage,
            'searchType' => $isSearchPage ? 'something' : $DisplayParams->searchType,
            'tagsArr' => $tagsArr,
            'tagsArrForSelect' => $tagsArrForSelect,
            'templatesArr' => $this->Templates->readForUser(),
            'visibilityArr' => $visibilityArr,
            'extendedError' => $extendedError,
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
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        $this->Entity->canOrExplode('read');

        // REVISIONS
        $Revisions = new Revisions(
            $this->Entity,
            (int) $this->App->Config->configArr['max_revisions'],
            (int) $this->App->Config->configArr['min_delta_revisions'],
            (int) $this->App->Config->configArr['min_days_revisions'],
        );

        $template = 'view.html';

        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'commentsArr' => $this->Entity->Comments->read(new ContentParams()),
            'linksArr' => $this->Entity->Links->read(new ContentParams()),
            'mode' => 'view',
            'revNum' => $Revisions->readCount(),
            'stepsArr' => $this->Entity->Steps->read(new ContentParams()),
            'templatesArr' => $this->Templates->readForUser(),
            'timestamperFullname' => $this->Entity->getTimestamperFullname(),
            'uploadsArr' => $this->Entity->Uploads->readAllNormal(),
        );

        // RELATED ITEMS AND EXPERIMENTS
        if ($this->Entity->type === 'items') {
            ['items' => $renderArr['relatedItemsArr'],
                'experiments' => $renderArr['relatedExperimentsArr']
            ] = $this->Entity->Links->readRelated();
        }

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

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

        // VISIBILITY ARR
        $TeamGroups = new TeamGroups($this->Entity->Users);

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'deletableXp' => $this->getDeletableXp(),
            'itemsCategoryArr' => $itemsCategoryArr,
            'lang' => Tools::getCalendarLang($this->App->Users->userData['lang'] ?? 'en_GB'),
            'lastModifierFullname' => $lastModifierFullname,
            'linksArr' => $this->Entity->Links->read(new ContentParams()),
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'mode' => 'edit',
            'revNum' => $Revisions->readCount(),
            'stepsArr' => $this->Entity->Steps->read(new ContentParams()),
            'templatesArr' => $this->Templates->readForUser(),
            'uploadsArr' => $this->Entity->Uploads->readAllNormal(),
            'visibilityArr' => $TeamGroups->getVisibilityList(),
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));
        return $Response;
    }

    /**
     * Can we delete experiments? This is used to disable the Delete button in menu.
     */
    private function getDeletableXp(): bool
    {
        // get the config option from team setting
        $Team = new Team($this->App->Users->team);
        $deletableXp = (bool) $Team->getDeletableXp();
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

    private function prepareAdvancedSearchQuery(array $visibilityArr, array $teamGroups): string
    {
        $searchException = '';
        if ($this->App->Request->query->has('q') && !empty($this->App->Request->query->get('q'))) {
            $query = trim((string) $this->App->Request->query->get('q'));

            $advancedQuery = new AdvancedSearchQuery($query, new VisitorParameters($this->Entity->type, $visibilityArr, $teamGroups));
            $whereClause = $advancedQuery->getWhereClause();
            if ($whereClause) {
                $this->Entity->addToExtendedFilter($whereClause['where'], $whereClause['bindValues']);
            }

            $searchException = $advancedQuery->getException();
        }

        return $searchException === '' ? '' : 'Search error: ' . $searchException;
    }
}
