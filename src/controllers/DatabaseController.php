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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Elabftw\Database;
use Elabftw\Elabftw\Revisions;
use Elabftw\Elabftw\UploadsView;
use Elabftw\Elabftw\ItemsTypes;
use Elabftw\Elabftw\TeamGroups;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\App;
use Elabftw\Interfaces\ControllerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For database.php
 */
class DatabaseController extends EntityController
{
    /** @var string $page the corresponding page */
    private $page;

    /** @var Entity $Entity instance of Entity */
    private $Entity;

    /** @var categoryArr $categoryArr array of category (status or item type) */
    private $categoryArr;

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->page = 'database.php';

        $this->Entity = new Database($this->App->Users);

        $Category = new ItemsTypes($this->App->Users);
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
        // check permissions
        $this->Entity->canOrExplode('read');
        $UploadsView = new UploadsView($this->Entity->Uploads);

        // REVISIONS
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();

        // COMMENTS
        $commentsArr = $this->Entity->Comments->readAll();

        $template = 'view.html';
        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'Entity' => $this->Entity,
            'Uv' => $UploadsView,
            'commentsArr' => $commentsArr,
            'mode' => 'view',
            'revNum' => $revNum
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
        // a locked item cannot be edited
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('This item is locked. You cannot edit it!'));
        }

        $ItemsTypes = new ItemsTypes($this->Entity->Users);
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();
        $UploadsView = new UploadsView($this->Entity->Uploads);
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'Categories' => $ItemsTypes,
            'Uv' => $UploadsView,
            'categoryArr' => $this->categoryArr,
            'mode' => 'edit',
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'revNum' => $revNum,
            'visibilityArr' => $visibilityArr
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
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        // if this variable is not empty the error message shown will be different if there are no results
        $searchType = null;
        $query = '';
        $getTags = false;

        // CATEGORY FILTER
        if (Tools::checkId((int) $this->App->Request->query->get('cat')) !== false) {
            $this->Entity->categoryFilter = "AND items_types.id = " . $this->App->Request->query->get('cat');
            $searchType = 'category';
        }
        // TAG FILTER
        if ($this->App->Request->query->get('tag') != '') {
            $tag = filter_var($this->App->Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $this->Entity->tagFilter = "AND tags.tag LIKE '" . $tag . "'";
            $searchType = 'tag';
            $getTags = true;
        }
        // QUERY FILTER
        if ($this->App->Request->query->get('q') != '') {
            $query = filter_var($this->App->Request->query->get('q'), FILTER_SANITIZE_STRING);
            $this->Entity->queryFilter = "AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%')";
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
            $this->Entity->order = 'items_types.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $this->Entity->order = 'items.' . $order;
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
        $limit = (int) $this->App->Users->userData['limit_nb'];
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

        $itemsArr = $this->Entity->read($getTags);

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'Request' => $this->App->Request,
            'categoryArr' => $this->categoryArr,
            'itemsArr' => $itemsArr,
            'offset' => $offset,
            'query' => $query,
            'searchType' => $searchType,
            'visibilityArr' => $visibilityArr
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }
}
