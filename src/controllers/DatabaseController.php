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
use Elabftw\Models\Database;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Revisions;
use Elabftw\Models\TeamGroups;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Response;

/**
 * For database.php
 */
class DatabaseController extends AbstractEntityController
{
    /**
     * Constructor
     *
     * @param App $app
     * @param Database $entity
     */
    public function __construct(App $app, Database $entity)
    {
        parent::__construct($app, $entity);

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
        $this->Entity->canOrExplode('read');

        // LINKS
        $linksArr = $this->Entity->Links->readAll();

        // STEPS
        $stepsArr = $this->Entity->Steps->readAll();

        // REVISIONS
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();

        // COMMENTS
        $commentsArr = $this->Entity->Comments->readAll();

        $template = 'view.html';
        // the mode parameter is for the uploads tpl
        $renderArr = array(
            'Entity' => $this->Entity,
            'commentsArr' => $commentsArr,
            'mode' => 'view',
            'revNum' => $revNum,
            'linksArr' => $linksArr,
            'stepsArr' => $stepsArr,
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

        // LINKS
        $linksArr = $this->Entity->Links->readAll();

        // STEPS
        $stepsArr = $this->Entity->Steps->readAll();

        $ItemsTypes = new ItemsTypes($this->Entity->Users);
        $Revisions = new Revisions($this->Entity);
        $revNum = $Revisions->readCount();
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'Categories' => $ItemsTypes,
            'categoryArr' => $this->categoryArr,
            'mode' => 'edit',
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'revNum' => $revNum,
            'visibilityArr' => $visibilityArr,
            'linksArr' => $linksArr,
            'stepsArr' => $stepsArr,
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

        // CATEGORY FILTER
        if (Check::id((int) $this->App->Request->query->get('cat')) !== false) {
            $this->Entity->addFilter('items_types.id', $this->App->Request->query->get('cat'));
            $searchType = 'category';
        }
        // TAG FILTER
        if (!empty($this->App->Request->query->get('tags')[0])) {
            // get all the ids with that tag
            $ids = $this->Entity->Tags->getIdFromTags($this->App->Request->query->get('tags'), (int) $this->App->Users->userData['team']);
            $idFilter = ' AND (';
            foreach ($ids as $id) {
                $idFilter .= 'entity.id = ' . $id . ' OR ';
            }
            $idFilter = rtrim($idFilter, ' OR ');
            $idFilter .= ')';
            $this->Entity->idFilter = $idFilter;
            $searchType = 'tag';
        }
        // QUERY FILTER
        if (!empty($this->App->Request->query->get('q'))) {
            $query = filter_var($this->App->Request->query->get('q'), FILTER_SANITIZE_STRING);
            if ($query !== false) {
                $this->Entity->queryFilter = Tools::getSearchSql($query);
                $searchType = 'query';
            }
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
            $this->Entity->order = 'entity.id';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title' || $order === 'id' || $order === 'lastchange') {
            $this->Entity->order = 'entity.' . $order;
        } elseif ($order === 'comment') {
            $this->Entity->order = 'commentst.recent_comment';
        } elseif ($order === 'user') {
            $this->Entity->order = 'entity.userid';
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
        $limit = (int) ($this->App->Users->userData['limit_nb'] ?? 15);
        if ($this->App->Request->query->has('limit')) {
            $limit = Check::limit((int) $this->App->Request->query->get('limit'));
        }

        $offset = 0;
        if ($this->App->Request->query->has('offset') && Check::id((int) $this->App->Request->query->get('offset')) !== false) {
            $offset = (int) $this->App->Request->query->get('offset');
        }

        $this->Entity->setOffset($offset);
        $this->Entity->setLimit($limit);
        // END PAGINATION

        // store the query parameters in the Session
        $this->App->Session->set('lastquery', $this->App->Request->query->all());

        // only show public to anon
        if ($this->App->Session->get('anon')) {
            $this->Entity->addFilter('entity.canread', 'public');
        }

        $itemsArr = $this->Entity->readShow();
        // get tags separately
        $tagsArr = $this->Entity->getTags($itemsArr);

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $this->Entity,
            'Request' => $this->App->Request,
            'categoryArr' => $this->categoryArr,
            'itemsArr' => $itemsArr,
            'limit' => $limit,
            'offset' => $offset,
            'query' => $query,
            'tagsArr' => $tagsArr,
            'searchType' => $searchType,
            'visibilityArr' => $visibilityArr,
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }
}
