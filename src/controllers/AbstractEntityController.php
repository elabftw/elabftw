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
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Revisions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Response;

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
        // VISIBILITY LIST
        $TeamGroups = new TeamGroups($this->Entity->Users);

        // CATEGORY FILTER
        if (Check::id((int) $this->App->Request->query->get('cat')) !== false) {
            $this->Entity->addFilter('categoryt.id', $this->App->Request->query->getDigits('cat'));
        }
        // OWNER (USERID) FILTER
        if ($this->App->Request->query->has('owner') && !$isSearchPage) {
            $owner = (int) $this->App->Request->query->get('owner');
            $this->Entity->addFilter('entity.userid', (string) $owner);
        }

        // TAG FILTER
        if (!empty(((array) $this->App->Request->query->get('tags'))[0])) {
            // get all the ids with that tag
            $tagsFromGet = (array) $this->App->Request->query->get('tags');
            $tagsFromGet = array_map(function ($t) {
                return (string) $t;
            }, $tagsFromGet);
            $ids = $this->Entity->Tags->getIdFromTags($tagsFromGet, (int) $this->App->Users->userData['team']);
            $idFilter = ' AND (';
            foreach ($ids as $id) {
                $idFilter .= 'entity.id = ' . $id . ' OR ';
            }
            $trimmedFilter = rtrim($idFilter, ' OR ') . ')';
            // don't add it if it's empty (for instance we search in items for a tag that only exists on experiments)
            if ($trimmedFilter === ' AND ()') {
                throw new ImproperActionException(_("Sorry. I couldn't find anything :("));
            }
            $this->Entity->idFilter = $trimmedFilter;
        }

        // create the DisplayParams object from the query
        $DisplayParams = new DisplayParams();
        $DisplayParams->adjust($this->App);

        // only show public to anon
        if ($this->App->Session->get('is_anon')) {
            $this->Entity->addFilter('entity.canread', 'public');
        }

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

        $template = 'show.html';

        $renderArr = array(
            'DisplayParams' => $DisplayParams,
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'pinnedArr' => $this->Entity->Pins->getPinned(),
            'itemsArr' => $itemsArr,
            // generate light show page
            'searchPage' => $isSearchPage,
            'searchType' => $isSearchPage ? 'something' : $DisplayParams->searchType,
            'tagsArr' => $tagsArr,
            'tagsArrForSelect' => $tagsArrForSelect,
            'templatesArr' => $this->Templates->readForUser(),
            'visibilityArr' => $TeamGroups->getVisibilityList(),
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
            'timestampInfo' => $this->Entity->getTimestampInfo(),
            'uploadsArr' => $this->Entity->Uploads->readAll(),
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
            'lang' => Tools::getCalendarLang($this->App->Users->userData['lang'] ?? 'en_GB'),
            'linksArr' => $this->Entity->Links->read(new ContentParams()),
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'mode' => 'edit',
            'revNum' => $Revisions->readCount(),
            'stepsArr' => $this->Entity->Steps->read(new ContentParams()),
            'templatesArr' => $this->Templates->readForUser(),
            'uploadsArr' => $this->Entity->Uploads->readAll(),
            'visibilityArr' => $TeamGroups->getVisibilityList(),
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));
        return $Response;
    }
}
