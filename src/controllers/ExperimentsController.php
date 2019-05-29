<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Revisions;
use Elabftw\Models\Status;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Symfony\Component\HttpFoundation\Response;

/**
 * For experiments.php
 */
class ExperimentsController extends AbstractEntityController
{
    /**
     * Constructor
     *
     * @param App $app
     * @param Experiments $entity
     */
    public function __construct(App $app, Experiments $entity)
    {
        parent::__construct($app, $entity);

        $Category = new Status($this->App->Users);
        $this->categoryArr = $Category->readAll();
    }

    /**
     * View mode (one item displayed)
     *
     * @return Response
     */
    protected function view(): Response
    {
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        $this->Entity->canOrExplode('read');

        // LINKS
        $linksArr = $this->Entity->Links->readAll();

        // STEPS
        $stepsArr = $this->Entity->Steps->readAll();

        // COMMENTS
        $commentsArr = $this->Entity->Comments->readAll();

        // REVISIONS
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();

        $timestampInfo = $this->Entity->getTimestampInfo();

        $template = 'view.html';
        $renderArr = array(
            'Entity' => $this->Entity,
            'linksArr' => $linksArr,
            'revNum' => $revNum,
            'stepsArr' => $stepsArr,
            'timestampInfo' => $timestampInfo,
            'commentsArr' => $commentsArr,
            'mode' => 'view',
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }

    /**
     * Edit mode
     *
     * @return Response
     */
    protected function edit(): Response
    {
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        // check permissions
        $this->Entity->canOrExplode('write');
        // a locked experiment cannot be edited
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('This item is locked. You cannot edit it!'));
        }

        // REVISIONS
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();

        // TEAM GROUPS
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        // LINKS
        $linksArr = $this->Entity->Links->readAll();

        // STEPS
        $stepsArr = $this->Entity->Steps->readAll();

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'lang' => Tools::getCalendarLang($this->App->Users->userData['lang']),
            'linksArr' => $linksArr,
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'mode' => 'edit',
            'revNum' => $revNum,
            'stepsArr' => $stepsArr,
            'visibilityArr' => $visibilityArr,
        );

        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));
        return $Response;
    }

    /**
     * Show mode (several items displayed). Default view.
     *
     * @return Response
     */
    protected function show(): Response
    {
        $searchType = '';
        $tag = '';
        $query = '';
        $getTags = false;

        // CATEGORY FILTER
        if (Tools::checkId((int) $this->App->Request->query->get('cat')) !== false) {
            $this->Entity->categoryFilter = ' AND status.id = ' . $this->App->Request->query->get('cat');
            $searchType = 'filter';
        }
        // TAG FILTER
        if (!empty($this->App->Request->query->get('tags'))) {
            $having = 'HAVING ';
            foreach ($this->App->Request->query->get('tags') as $tag) {
                $tag = \filter_var($tag, FILTER_SANITIZE_STRING);
                $having .= "tags LIKE '%$tag%' AND ";
            }
            $this->Entity->tagFilter = rtrim($having, ' AND');
            $searchType = 'tag';
            $getTags = true;
        }
        // QUERY FILTER
        if ($this->App->Request->query->get('q') != '') {
            $query = filter_var($this->App->Request->query->get('q'), FILTER_SANITIZE_STRING);
            $this->Entity->queryFilter = " AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%' OR
                elabid LIKE '%$query%'
            )";
            $searchType = 'query';
        }
        // ORDER
        $order = '';

        // load the pref from the user
        if (isset($this->Entity->Users->userData['orderby'])) {
            $order = $this->Entity->Users->userData['orderby'];
        }

        // now get pref from the filter-order-sort menu
        if ($this->App->Request->query->has('order') && !empty($this->App->Request->query->get('order'))) {
            $order = $this->App->Request->query->get('order');
        }

        if ($order === 'cat') {
            $this->Entity->order = 'status.id';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title' || $order === 'id') {
            $this->Entity->order = 'experiments.' . $order;
        } elseif ($order === 'comment') {
            $this->Entity->order = 'experiments_comments.recent_comment';
        }

        // SORT
        $sort = '';

        // load the pref from the user
        if (isset($this->Entity->Users->userData['sort'])) {
            $sort = $this->Entity->Users->userData['sort'];
        }

        // now get pref from the filter-order-sort menu
        if ($this->App->Request->query->has('sort') && !empty($this->App->Request->query->get('sort'))) {
            $sort = $this->App->Request->query->get('sort');
        }

        if ($sort === 'asc' || $sort === 'desc') {
            $this->Entity->sort = $sort;
        }

        // PAGINATION
        $limit = (int) $this->Entity->Users->userData['limit_nb'] ?? 15;
        if ($this->App->Request->query->has('limit') && Tools::checkId((int) $this->App->Request->query->get('limit')) !== false) {
            $limit = (int) $this->App->Request->query->get('limit');
        }

        $offset = 0;
        if ($this->App->Request->query->has('offset') && Tools::checkId((int) $this->App->Request->query->get('offset')) !== false) {
            $offset = (int) $this->App->Request->query->get('offset');
        }

        $this->Entity->setOffset($offset);
        $this->Entity->setLimit($limit);
        // END PAGINATION

        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        $Templates = new Templates($this->Entity->Users);
        $templatesArr = $Templates->readAll();

        // READ ALL ITEMS

        if ($this->App->Request->getSession()->get('anon')) {
            $this->Entity->visibilityFilter = "AND experiments.visibility = 'public'";
            $itemsArr = $this->Entity->read($getTags);
        // related filter
        } elseif (Tools::checkId((int) $this->App->Request->query->get('related')) !== false) {
            $searchType = 'related';
            $itemsArr = $this->Entity->readRelated((int) $this->App->Request->query->get('related'));
        } else {
            // filter by user only if we are not making a search
            if (!$this->Entity->Users->userData['show_team'] && ($searchType === '' || $searchType === 'filter')) {
                $this->Entity->setUseridFilter();
            }

            $itemsArr = $this->Entity->read($getTags);
        }

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'categoryArr' => $this->categoryArr,
            'itemsArr' => $itemsArr,
            'offset' => $offset,
            'query' => $query,
            'searchType' => $searchType,
            'tag' => $tag,
            'templatesArr' => $templatesArr,
            'visibilityArr' => $visibilityArr,
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }
}
