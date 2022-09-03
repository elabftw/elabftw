<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use function dirname;
use Elabftw\Elabftw\CreateTemplate;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AbstractCategory;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Status;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Check;
use Elabftw\Services\MakeBackupZip;
use Exception;
use function implode;
use function in_array;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

/**
 * For API v1 requests
 */
class Apiv1Controller extends AbstractApiController
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private AbstractCategory | ItemsTypes $Category;

    /** @psalm-suppress PropertyNotSetInConstructor */
    //@phpstan-ignore-next-line
    private $Entity;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Scheduler $Scheduler;

    private array $allowedMethods = array('GET', 'POST', 'DELETE');

    public function getResponse(): Response
    {
        try {
            $this->parseReq();
            // GET ENTITY/CATEGORY
            switch ($this->Request->getMethod()) {
                // GET REQUEST
                case Request::METHOD_GET:
                    return match ($this->endpoint) {
                        'uploads' => $this->getUpload(),
                        'backupzip' => $this->getBackupZip(),
                        'experiments', 'items' => $this->getEntity(),
                        'templates' => $this->getTemplate(),
                        'status', 'items_types' => $this->getCategory(),
                        'bookable' => $this->getBookable(),
                        'events' => $this->getEvents(),
                        'tags' => $this->getTags(),
                        default => throw new ImproperActionException('Invalid endpoint.'),
                    };

                    // POST REQUEST
                case Request::METHOD_POST:
                    // FILE UPLOAD
                    if ($this->Request->files->count() > 0) {
                        return $this->uploadFile();
                    }

                    // TITLE DATE BODY METADATA UPDATE
                    if (($this->Request->request->has('date') ||
                        $this->Request->request->has('title') ||
                        $this->Request->request->has('bodyappend') ||
                        $this->Request->request->has('body') ||
                        $this->Request->request->has('metadata')) &&
                        ($this->endpoint === 'experiments' || $this->endpoint === 'items' || $this->endpoint === 'templates' || $this->endpoint === 'items_types')) {
                        return $this->updateEntity();
                    }

                    // ADD TAG
                    if ($this->Request->request->has('tag')) {
                        return $this->createTag();
                    }

                    // ADD LINK
                    if ($this->Request->request->has('link')) {
                        return $this->createLink();
                    }

                    // CHANGE CATEGORY
                    if ($this->Request->request->has('category')) {
                        return $this->updateCategory();
                    }

                    // CREATE EVENT
                    if ($this->endpoint === 'events') {
                        return $this->createEvent();
                    }

                    // CREATE AN EXPERIMENT/ITEM
                    if ($this->Entity instanceof Items) {
                        return $this->createItem();
                    } elseif ($this->Entity instanceof Templates) {
                        return $this->createTemplate();
                    }
                    return $this->createExperiment();

                case Request::METHOD_DELETE:
                    return $this->destroyEvent();
                default:
                    // send error 405 for Method Not Allowed, with Allow header as per spec:
                    // https://tools.ietf.org/html/rfc7231#section-7.4.1
                    return new Response('Invalid HTTP request method!', 405, array('Allow' => implode(', ', $this->allowedMethods)));
            }
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(array('result' => $e->getMessage()), 404);
        } catch (UnauthorizedException $e) {
            // send error 401 if it's lacking an Authorization header, with WWW-Authenticate header as per spec:
            // https://tools.ietf.org/html/rfc7235#section-3.1
            return new Response($e->getMessage(), 401, array('WWW-Authenticate' => 'Bearer'));
        } catch (ImproperActionException $e) {
            return new Response($e->getMessage(), 400);
        } catch (IllegalActionException $e) {
            return new Response(Tools::error(true), 403);
        } catch (Exception $e) {
            return new Response(Tools::error(), 500);
        }
    }

    /**
     * Set the id and endpoints fields
     */
    protected function parseReq(): array
    {
        $req = parent::parseReq();

        // load Entity
        // if endpoint is uploads we don't actually care about the entity type
        switch ($this->endpoint) {
            case 'experiments':
            case 'uploads':
            case 'backupzip':
            case 'tags':
                $this->Entity = new Experiments($this->Users, $this->id);
                break;
            case 'items':
            case 'bookable':
                $this->Entity = new Items($this->Users, $this->id);
                break;
            case 'templates':
                $this->Entity = new Templates($this->Users, $this->id);
                break;
            case 'items_types':
                $this->Category = new ItemsTypes($this->Users);
                break;
            case 'status':
                $this->Category = new Status(new Teams($this->Users, $this->Users->team));
                break;
            case 'events':
                $this->Entity = new Items($this->Users, $this->id);
                $defaultStart = '2018-12-23T00:00:00+01:00';
                $defaultEnd = '2119-12-23T00:00:00+01:00';
                $this->Scheduler = new Scheduler(
                    $this->Entity,
                    null,
                    (string) $this->Request->query->get('start', $defaultStart),
                    (string) $this->Request->query->get('end', $defaultEnd),
                );
                break;
            default:
                throw new ImproperActionException('Invalid endpoint.');
        }
        return $req;
    }

    /**
     * @apiDefine GetEntity
     * @apiParam {Number} [id] Entity id
     */

    /**
     * @api {get} /items/[:id] Read items from database
     * @apiName GetItem
     * @apiGroup Entity
     * @apiDescription Get the data from items or just one item if id is set.
     * @apiUse GetEntity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get all items
     * all_items = manager.get_all_items()
     * # get item with id 42
     * item = manager.get_item(42)
     * print(json.dumps(item, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get all items
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items
     * # get item with id 42
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * @apiQuery {String} limit Limit the number of results returned
     * @apiQuery {String} offset Offset for results returned
     * @apiQuery {String} search Search string to look for something
     * @apiSuccess {String} body Main content
     * @apiSuccess {String} category Item type
     * @apiSuccess {Number} category_id Id of the item type
     * @apiSuccess {String} color Hexadecimal color code for the item type
     * @apiSuccess {Number} date Date in YYYYMMDD format
     * @apiSuccess {String} fullname Name of the owner of the experiment
     * @apiSuccess {Number} has_attachment Number of files attached
     * @apiSuccess {Number} id Id of the item
     * @apiSuccess {String} elabid Unique elabid of the item
     * @apiSuccess {Number} locked 0 if not locked, 1 if locked
     * @apiSuccess {Number} rating Number of stars
     * @apiSuccess {String} tags Tags separated by '|'
     * @apiSuccess {String} tags_id Id of the tags separated by ','
     * @apiSuccess {Number} team Id of the team
     * @apiSuccess {String} title Title of the experiment
     * @apiSuccess {String} category See category_id
     * @apiSuccess {String} up_item_id Id of the uploaded items
     * @apiSuccess {String[]} uploads Array of uploaded files
     * @apiSuccess {Number} userid User id of the owner
     */

    /**
     * @api {get} /experiments/[:id] Read experiments
     * @apiName GetExperiment
     * @apiGroup Entity
     * @apiDescription Get the data from experiments or just one experiment if id is set.
     * @apiUse GetEntity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get all experiments
     * all_exp = manager.get_all_experiments()
     * # get experiment with id 42
     * exp = manager.get_experiment(42)
     * print(json.dumps(exp, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get all experiments
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments
     * # get experiment with id 42
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * @apiQuery {String} limit Limit the number of results returned
     * @apiQuery {String} offset Offset for results returned
     * @apiQuery {String} search Search string to look for something
     * @apiSuccess {String} body Main content
     * @apiSuccess {String} category Status
     * @apiSuccess {Number} category_id Id of the status
     * @apiSuccess {String} color Hexadecimal color code for the status
     * @apiSuccess {Number} date Date in YYYYMMDD format
     * @apiSuccess {DateTime} created_at Timestamp of when the experiment was created
     * @apiSuccess {String} elabid Unique elabid of the experiment
     * @apiSuccess {String} fullname Name of the owner of the experiment
     * @apiSuccess {Number} has_attachment Number of files attached
     * @apiSuccess {Number} id Id of the experiment
     * @apiSuccess {Number} locked 0 if not locked, 1 if locked
     * @apiSuccess {Number} lockedby 1 User id of the locker
     * @apiSuccess {DateTime} lockedwhen Time when it was locked
     * @apiSuccess {String} next_step Next step to execute
     * @apiSuccess {DateTime} recent_comment Date and time of the most recent comment
     * @apiSuccess {String} tags Tags separated by '|'
     * @apiSuccess {String} tags_id Id of the tags separated by ','
     * @apiSuccess {Number} team Id of the team
     * @apiSuccess {Number} timestamped 0 if not timestamped, 1 if timestamped
     * @apiSuccess {Number} timestampedby User id of the timestamper
     * @apiSuccess {DateTime} timestampedwhen Date and time of the timestamp
     * @apiSuccess {String} timestampedtoken Full path to the token file
     * @apiSuccess {String} title Title of the experiment
     * @apiSuccess {String} up_item_id Id of the uploaded items
     * @apiSuccess {String[]} uploads Array of uploaded files
     * @apiSuccess {Number} userid User id of the owner
     * @apiSuccess {String} canread Read permission of the experiment
     * @apiSuccess {String} canwrite Write permission of the experiment
     *
     */
    private function getEntity(): Response
    {
        if ($this->id === null) {
            $DisplayParams = new DisplayParams($this->Users, $this->Request);

            // use our limit/offset
            // remove 1 to limit as there is 1 added in the sql query
            $DisplayParams->limit = $this->limit - 1;
            $DisplayParams->offset = $this->offset;
            if ($this->search) {
                $TeamGroups = new TeamGroups($this->Users);
                $advancedQuery = new AdvancedSearchQuery(
                    $this->search,
                    new VisitorParameters(
                        $this->Entity->type,
                        $TeamGroups->getVisibilityList(),
                        $TeamGroups->readGroupsWithUsersFromUser(),
                    ),
                );
                $whereClause = $advancedQuery->getWhereClause();
                if ($whereClause) {
                    $this->Entity->addToExtendedFilter($whereClause['where'], $whereClause['bindValues']);
                }
                $error = $advancedQuery->getException();
                if ($error) {
                    return new JsonResponse(array('result' => 'error', 'message' => $error));
                }
            }

            return new JsonResponse($this->Entity->readShow($DisplayParams, false));
        }
        return new JsonResponse($this->Entity->readOne());
    }

    /**
     * @api {get} /templates/[:id] Read templates
     * @apiName GetTemplate
     * @apiGroup Entity
     * @apiDescription Get the data from templates or just one template if id is set.
     * @apiUse GetEntity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get all templates
     * all_tpl = manager.get_all_templates()
     * # get template with id 42
     * tpl = manager.get_template(42)
     * print(json.dumps(tpl, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get all templates
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/templates
     * # get template with id 42
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/templates/42
     * @apiSuccess {String} body Main content
     * @apiSuccess {Number} date Date in YYYYMMDD format
     * @apiSuccess {String} fullname Name of the owner of the experiment
     * @apiSuccess {Number} id Id of the experiment
     * @apiSuccess {Number} locked 0 if not locked, 1 if locked
     * @apiSuccess {Number} lockedby 1 User id of the locker
     * @apiSuccess {DateTime} lockedwhen Time when it was locked
     * @apiSuccess {String} tags Tags separated by '|'
     * @apiSuccess {String} tags_id Id of the tags separated by ','
     * @apiSuccess {String} title Title of the experiment
     * @apiSuccess {Number} userid User id of the owner
     * @apiSuccess {String} canread Read permission of the experiment
     * @apiSuccess {String} canwrite Write permission of the experiment
     *
     */
    private function getTemplate(): Response
    {
        if ($this->id === null && $this->Entity instanceof Templates) {
            return new JsonResponse($this->Entity->readAll());
        }
        return new JsonResponse($this->Entity->readOne());
    }

    /**
     * @api {get} /tags Read tags
     * @apiName GetTags
     * @apiGroup Entity
     * @apiDescription Read tags from the team
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get tags
     * all_tags = manager.get_tags()
     * print(json.dumps(all_tags, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get all experiments
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/tags
     * @apiSuccess {String} tag Tag content
     * @apiSuccess {Number} id Tag id
     */
    private function getTags(): Response
    {
        return new JsonResponse($this->Entity->Tags->readAll());
    }

    /**
     * @api {get} /backupzip/:period Get backup zip
     * @apiName GetBackupZip
     * @apiGroup Backup
     * @apiParam {String} period time period FROM-TO in the format YYYYMMDD-YYYMMDD (e.g. 20191129-20200501)
     * @apiPermission Sysadmin
     * @apiDescription Get a zip with the experiments from a time period, ordered by users, only the ones changed in the time period
     * @apiExample {python} Python example
     * import elabapy
     * import datetime
     * from datetime import timedelta
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get all modified experiments from last week
     * now = datetime.datetime.now()
     * lastweek = now - timedelta(weeks=1)
     * period = "-".join((lastweek.strftime('%Y%m%d'), now.strftime('%Y%m%d')))
     * with open(period + '.zip', 'wb') as zipfile:
     *     zipfile.write(manager.get_backup_zip(period))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * curl -H "Authorization: $TOKEN" "https://elab.example.org/api/v1/backupzip/202000224-20200701" --output out.zip
     * @apiSuccess {Binary} zip Zip file with experiments modified during `period`
     */
    private function getBackupZip(): Response
    {
        // only let a sysadmin get that
        if (!$this->Users->userData['is_sysadmin']) {
            throw new IllegalActionException('Only a sysadmin can use this endpoint!');
        }
        $opt = new ArchiveOptions();
        // crucial option for a stream input
        $opt->setZeroHeader(true);
        $Zip = new ZipStream(null, $opt);
        $Make = new MakeBackupZip($Zip, $this->Entity, $this->param);
        $Response = new StreamedResponse();
        $Response->setCallback(function () use ($Make) {
            $Make->getStreamZip();
        });
        return $Response;
    }

    /**
     * @api {get} /bookable/ Get bookable items
     * @apiName GetBookable
     * @apiGroup Entity
     * @apiDescription Get only the bookable items
     * @apiExample {python} Python example
     * import elabapy
     * import json
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * bookable = manager.get_bookable()
     * print(json.dumps(bookable, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/bookable
     */
    private function getBookable(): Response
    {
        $this->Entity->addFilter('bookable', '1');
        return $this->getEntity();
    }

    /**
     * @api {get} /events/[:id] Get events
     * @apiName GetEvent
     * @apiGroup Events
     * @apiDescription Get all the events from the team or just one event if the id is set
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * event = manager.get_event(2)
     * print(json.dumps(event, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get info about event with id 2
     * curl -H "Authorization: $TOKEN" "https://elab.example.org/api/v1/events/2"
     * @apiSuccess {Number} id Id of the event
     * @apiSuccess {Number} team Id of the team
     * @apiSuccess {Number} item Booked item
     * @apiSuccess {String} start Start date/time
     * @apiSuccess {String} end End date/time
     * @apiSuccess {String} title Comment for the event
     * @apiSuccess {Number} userid Id of the user that booked it
     * @apiSuccess {Number} experiment Id of the bound experiment
     * @apiSuccessExample {json} Success-Response:
     * {
     *     "id":"2",
     *     "team":"1",
     *     "item":"105",
     *     "start":"2019-11-29T09:30:00",
     *     "end":"2019-11-29T14:30:00",
     *     "title":"ahaha",
     *     "userid":"1",
     *     "experiment": "12"
     * }
     * @apiErrorExample {json} Error-Response:
     * HTTP/2 404 NOT FOUND
     * {
     *     "result": "No data associated with that id"
     * }
     */

    /**
     * Get events from the team
     */
    private function getEvents(): Response
    {
        // return all events if there is no id
        if ($this->id === null) {
            return new JsonResponse($this->Scheduler->readAll());
        }
        $this->Scheduler->setId($this->id);
        return new JsonResponse($this->Scheduler->readOne());
    }

    /**
     * @api {get} /uploads/:id Get an upload
     * @apiName GetUpload
     * @apiGroup Entity
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # get upload with id 42
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/uploads/42
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # get upload with id 42
     * with open('example.gz', 'wb') as datafile:
     *     datafile.write(manager.get_upload(42))
     * @apiSuccess {Binary} None the file
     * @apiSuccessExample Success-Response:
     *     HTTP/2 200 OK
     *     [BINARY DATA]
     */

    /**
     * Get the file corresponding to the ID
     */
    private function getUpload(): Response
    {
        if ($this->id === null) {
            return new Response('You need to specify an ID!', 400);
        }
        // note: we don't really care about this entity yet
        $Uploads = new Uploads($this->Entity, $this->id);
        // now we know the id and type of the entity
        // so get the Entity to check for read permissions
        if ($Uploads->uploadData['type'] === 'experiments') {
            $Entity = new Experiments($this->Users, $Uploads->uploadData['item_id']);
        } elseif ($Uploads->uploadData['type'] === 'items') {
            $Entity = new Items($this->Users, $Uploads->uploadData['item_id']);
        } else {
            return new Response('Invalid upload id', 400);
        }

        try {
            // make sure we have read access to that entity
            $Entity->canOrExplode('read');
        } catch (IllegalActionException) {
            return new Response('You do not have permission to access this resource.', 403);
        }
        // TODO use download controller for s3 storage
        $filePath = dirname(__DIR__, 2) . '/uploads/' . $Uploads->uploadData['long_name'];
        return new BinaryFileResponse($filePath);
    }

    /**
     * @api {get} /items_types Get the list of id of available items_types
     * @apiName GetItemsTypes
     * @apiGroup Category
     * @apiExample {python} Python example
     * import elabapy
     * import json
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * items_types = manager.get_items_types()
     * print(json.dumps(items_types, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items_types
     * @apiSuccess {Json[]} None list of items_types for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *         [
     *           {
     *             "category_id": "1",
     *             "category": "Project",
     *             "color": "32a100",
     *             "bookable": "0",
     *             "body": "Some text",
     *             "ordering": "1",
     *             "canread": "team",
     *             "canwrite": "team"
     *           },
     *           {
     *             "category_id": "2",
     *             "category": "Microscope",
     *             "color": "2000eb",
     *             "bookable": "1",
     *             "body": "Template text",
     *             "ordering": "2",
     *             "canread": "team",
     *             "canwrite": "team"
     *           }
     *         ]
     *     }
     */
    /**
     * @api {get} /status Get the list of status for current team
     * @apiName GetStatus
     * @apiGroup Category
     * @apiExample {python} Python example
     * import elabapy
     * import json
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * items_types = manager.get_status()
     * print(json.dumps(items_types, indent=4, sort_keys=True))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * curl -H "Authorization: $TOKEN" https://elab.example.org/api/v1/status
     * @apiSuccess {Json[]} None list of status for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *         [
     *           {
     *             "category_id": "1",
     *             "category": "Running",
     *             "color": "3360ff",
     *             "is_default": "1"
     *           },
     *           {
     *             "category_id": "2",
     *             "category": "Success",
     *             "color": "54aa08",
     *             "is_default": "0"
     *             }
     *         ]
     *     }
     */

    /**
     * Get items_types or status list for current team
     */
    private function getCategory(): Response
    {
        return new JsonResponse($this->Category->readAll());
    }

    /**
     * @api {post} /experiments Create experiment
     * @apiName CreateExperiment
     * @apiGroup Entity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * response = manager.create_experiment()
     * print(f"Created experiment with id {response['id']}.")
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # create an experiment with default status
     * curl -X POST -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments
     * @apiSuccess {String} result success or error message
     * @apiSuccess {String} id Id of the new experiment
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *       "result": "success",
     *       "id": 42
     *     }
     */
    private function createExperiment(): Response
    {
        $id = $this->Entity->create(0);
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {post} /templates Create template
     * @apiName CreateTemplate
     * @apiGroup Entity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * response = manager.create_template()
     * print(f"Created template with id {response['id']}.")
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # create a template with default status
     * curl -X POST -H "Authorization: $TOKEN" https://elab.example.org/api/v1/templates
     * @apiSuccess {String} result success or error message
     * @apiSuccess {String} id Id of the new template
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *       "result": "success",
     *       "id": 42
     *     }
     */
    private function createTemplate(): Response
    {
        $id = $this->Entity->create('created from api');
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {post} /items/:id Create a database item
     * @apiName CreateItem
     * @apiGroup Entity
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * response = manager.create_item(3)
     * print(f"Created database item with id {response['id']}.")
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # create a database item in category with id 3
     * curl -X POST -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/3
     * @apiSuccess {String} result success or error message
     * @apiSuccess {String} id Id of the new item type (category)
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *       "result": "success",
     *       "id": 42
     *     }
     */
    private function createItem(): Response
    {
        if ($this->id === null) {
            return new Response('Invalid id', 400);
        }
        // check that the id we have is a valid item type from our team
        $ItemsTypes = new ItemsTypes($this->Users);
        $validIds = array_column($ItemsTypes->readAll(), 'category_id');
        if (!in_array($this->id, $validIds, true)) {
            return new Response('Cannot create an item with an item type id not in your team!', 403);
        }

        $id = $this->Entity->create($this->id);
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {post} /experiments/:id Add a link
     * @apiName AddLink
     * @apiGroup Entity
     * @apiParam {Number} id Experiment id
     * @apiParam {Number} link Id of the database item to link to
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # link database item 106 to experiment 42
     * params = { "link": 106 }
     * print(manager.add_link_to_experiment(42, params)
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # link database item 106 to experiment 42
     * curl -X POST -F "link=106" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * @apiSuccess {String} result Success or error message
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *       "result": "success"
     *     }
     */
    private function createLink(): Response
    {
        $this->Entity->Links->setId($this->Request->request->getInt('link'));
        $this->Entity->Links->postAction(Action::Create, array());
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Add a tag
     * @apiName AddTag
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {String} tag Tag to add
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * params = { "tag": "some-tag"}
     * # add tag "some-tag" to experiment 42
     * print(manager.add_tag_to_experiment(42, params)
     * # add tag "some-tag" to database item 42
     * print(manager.add_tag_to_item(42, params)
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # add tag "some-tag" to experiment 42
     * curl -X POST -F "tag=some-tag" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * # add tag "some-tag" to database item 42
     * curl -X POST -F "tag=some-tag" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "tag": "my tag"
     *     }
     */
    private function createTag(): Response
    {
        $this->Entity->Tags->postAction(Action::Create, array('tag' => (string) $this->Request->request->get('tag')));
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /events/:id Create event
     * @apiName AddEvent
     * @apiGroup Events
     * @apiDescription Create an event in the scheduler for an item
     * @apiParam {String} start Start time in ISO8601 format
     * @apiParam {Number} end End time in ISO8601 format
     * @apiParam {String} title Comment for the booking
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # book database item 42 on the 30th of November 2019 from noon to 2pm
     * params = {
     *     "start": "2019-11-30T12:00:00+01:00",
     *     "end": "2019-11-30T14:00:00+01:00",
     *     "title": "Booked from API",
     * }
     * print(manager.create_event(42, params))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # book database item 42 on the 30th of November 2019 from noon to 2pm
     * curl -X POST -F "start=2019-11-30T12:00:00+01:00" -F "end=2019-11-30T14:00:00+01:00" -F "title=Booked from API" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/events/42
     * @apiSuccess {String} result Success
     * @apiSuccess {String} id Id of new event
     * @apiError {Number} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "start": "2019-11-30T12:00:00+01:00",
     *       "end": "2019-11-30T14:00:00+01:00",
     *       "title": "Booked from API"
     *     }
     */
    private function createEvent(): Response
    {
        if ($this->id === null) {
            throw new ImproperActionException('Item id missing!');
        }
        $this->Entity->setId($this->id);
        $id = $this->Scheduler->postAction(Action::Create, array(
            'start' => (string) $this->Request->request->get('start'),
            'end' => (string) $this->Request->request->get('end'),
            'title' => (string) $this->Request->request->get('title'),
        ));
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {delete} /events/:id Destroy event
     * @apiName DestroyEvent
     * @apiGroup Events
     * @apiDescription Delete an event
     * @apiParam {Number} id Id of the event
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # destroy event with id 13
     * print(manager.destroy_event(13))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # destroy event with id 13
     * curl -X DELETE -H "Authorization: $TOKEN" https://elab.example.org/api/v1/events/13
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     */
    private function destroyEvent(): Response
    {
        if ($this->id === null) {
            throw new ImproperActionException('Event id missing!');
        }
        $this->Scheduler->setId($this->id);
        $this->Scheduler->destroy();
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Update entity
     * @apiName UpdateEntity
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {String} body Main content
     * @apiParam {String} date Date
     * @apiParam {String} title Title
     * @apiParam {String} metadata JSON metadata
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # update experiment 42
     * params = { "title": "New title", "date": "20200504", "body": "New body content", "metadata": '{"foo":1, "bar":"elab!"}' }
     * print(manager.post_experiment(42, params))
     * # append to the body
     * params = { "bodyappend": "appended text<br>" }
     * print(manager.post_experiment(42, params))
     * # update database item 42
     * print(manager.post_item(42, params))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # update experiment 42
     * # you can independently update the title, date or body, or all at the same time
     * curl -X POST -F "title=a new title" -F "body=a new body" -F "date=20200504" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * # update database item 42 title only
     * curl -X POST -F "title=a new title" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * # you can also append to the body
     * curl -X POST -F "bodyappend=appended text" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * # you can also update the metadata
     * curl -X POST -F "metadata={\"foo\":1}" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "body": "New body to be updated.",
     *       "date": "20180308",
     *       "title": "New title"
     *     }
     */
    private function updateEntity(): Response
    {
        // make sure a locked entry cannot be updated
        if ($this->Entity->entityData['locked']) {
            return new Response('Cannot update a locked entry!', 403);
        }
        if ($this->Request->request->has('title')) {
            $this->Entity->patch(Action::Update, array('title' => (string) $this->Request->request->get('title')));
        }
        if ($this->Request->request->has('date')) {
            $this->Entity->patch(Action::Update, array('date' => (string) $this->Request->request->get('date')));
        }
        if ($this->Request->request->has('body')) {
            $this->Entity->patch(Action::Update, array('body' => (string) $this->Request->request->get('body')));
        }
        if ($this->Request->request->has('bodyappend')) {
            $this->Entity->patch(Action::Update, array('bodyappend' => (string) $this->Request->request->get('bodyappend')));
        }
        if ($this->Request->request->has('metadata')) {
            $this->Entity->patch(Action::Update, array('metadata' => (string) $this->Request->request->get('metadata')));
        }
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Update category
     * @apiName UpdateCategory
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {Number} category for items the item type id, for experiments the status id
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # update status of experiment 42
     * params = { "category": "2" }
     * print(manager.post_experiment(42, params))
     * # update database item 42
     * print(manager.post_item(42, params))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # update experiment 42
     * curl -X POST -F "category=2" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * # update database item 42
     * curl -X POST -F "category=2" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "category": "2"
     *     }
     */
    private function updateCategory(): Response
    {
        $this->Entity->patch(Action::Update, array('category' => (int) $this->Request->request->get('category')));
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Upload a file
     * @apiName AddFile
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {File} file File to upload
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # upload your-file.jpg to experiment 42
     * with open('your-file.jpg', 'r+b') as myfile:
     *     params = { 'file': myfile }
     *     print(manager.upload_to_experiment(42, params))
     * # upload your-file.jpg to database item 1337
     * with open('your-file.jpg', 'r+b') as myfile:
     *     params = { 'file': myfile }
     *     print(manager.upload_to_item(1337, params))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # upload your-file.jpg to experiment 42
     * curl -X POST -F "file=@your-file.jpg" -H "Authorization: $TOKEN" "https://elab.example.org/api/v1/experiments/42"
     * # upload your-file.jpg to database item 42
     * curl -X POST -F "file=@your-file.jpg" -H "Authorization: $TOKEN" "https://elab.example.org/api/v1/items/42"
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     */
    private function uploadFile(): Response
    {
        $realName = $this->Request->files->get('file')->getClientOriginalName();
        $filePath = $this->Request->files->get('file')->getPathname();

        $id = $this->Entity->Uploads->create(new CreateUpload($realName, $filePath));

        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }
}
