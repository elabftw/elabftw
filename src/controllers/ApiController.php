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
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractCategory;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Status;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\MakeBackupZip;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * For API requests
 */
class ApiController implements ControllerInterface
{
    private App $App;

    private Request $Request;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private AbstractCategory $Category;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private AbstractEntity $Entity;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Scheduler $Scheduler;

    private Users $Users;

    private array $allowedMethods = array('GET', 'POST', 'DELETE');

    private bool $canWrite = false;

    private ?int $id;

    // experiments, items or uploads
    private string $endpoint;

    // used by backupzip to get the period
    private string $param;

    public function __construct(App $app)
    {
        $this->App = $app;
        $this->Request = $app->Request;
        // Check if the Authorization Token was sent along
        if (!$this->Request->server->has('HTTP_AUTHORIZATION')) {
            throw new UnauthorizedException('No access token provided!');
        }

        $this->parseReq();
    }

    /**
     * Get Response from Request
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        try {
            // GET ENTITY/CATEGORY
            if ($this->Request->server->get('REQUEST_METHOD') === 'GET') {
                // GET UPLOAD
                if ($this->endpoint === 'uploads') {
                    return $this->getUpload();
                }
                if ($this->endpoint === 'backupzip') {
                    return $this->getBackupZip();
                }

                if ($this->endpoint === 'experiments' || $this->endpoint === 'items') {
                    return $this->getEntity();
                }
                if ($this->endpoint === 'items_types' || $this->endpoint === 'status') {
                    return $this->getCategory();
                }
                if ($this->endpoint === 'bookable') {
                    return $this->getBookable();
                }
                if ($this->endpoint === 'events') {
                    return $this->getEvents();
                }
            }


            // POST request
            if ($this->Request->server->get('REQUEST_METHOD') === 'POST') {
                // POST means write access for the access token
                if (!$this->canWrite) {
                    return new Response('Cannot use readonly key with POST method!', 403);
                }
                // FILE UPLOAD
                if ($this->Request->files->count() > 0) {
                    return $this->uploadFile();
                }

                // TITLE DATE BODY UPDATE
                if ($this->Request->request->has('date')) {
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

                if ($this->endpoint === 'events') {
                    return $this->createEvent();
                }

                // CREATE AN EXPERIMENT/ITEM
                if ($this->Entity instanceof Database) {
                    return $this->createItem();
                }
                return $this->createExperiment();
            }

            // DELETE requests
            if ($this->Request->server->get('REQUEST_METHOD') === 'DELETE') {
                return $this->destroyEvent();
            }
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(array('result' => $e->getMessage()), 404);
        }

        // send error 405 for Method Not Allowed, with Allow header as per spec:
        // https://tools.ietf.org/html/rfc7231#section-7.4.1
        return new Response('Invalid HTTP request method!', 405, array('Allow' => \implode(', ', $this->allowedMethods)));
    }

    /**
     * Set the id and endpoints fields
     *
     * @return void
     */
    private function parseReq(): void
    {
        $args = explode('/', rtrim($this->Request->query->get('req') ?? '', '/'));

        // assign the id if there is one
        $id = null;
        if (Check::id((int) end($args)) !== false) {
            $id = (int) end($args);
        }
        $this->id = $id;

        // assign the endpoint (experiments, items, uploads, items_types, status)
        $this->endpoint = array_shift($args);
        $this->param = array_shift($args) ?? '';

        // verify the key and load user info
        $ApiKeys = new ApiKeys(new Users());
        $keyArr = $ApiKeys->readFromApiKey($this->Request->server->get('HTTP_AUTHORIZATION') ?? '');
        $this->Users = new Users((int) $keyArr['userid'], (int) $keyArr['team']);
        $this->canWrite = (bool) $keyArr['canWrite'];

        // load Entity
        // if endpoint is uploads we don't actually care about the entity type
        if ($this->endpoint === 'experiments' || $this->endpoint === 'uploads' || $this->endpoint === 'backupzip') {
            $this->Entity = new Experiments($this->Users, $this->id);
        } elseif ($this->endpoint === 'items' || $this->endpoint === 'bookable') {
            $this->Entity = new Database($this->Users, $this->id);
        } elseif ($this->endpoint === 'items_types') {
            $this->Category = new ItemsTypes($this->Users);
        } elseif ($this->endpoint === 'status') {
            $this->Category = new Status($this->Users);
        } elseif ($this->endpoint === 'events') {
            $this->Entity = new Database($this->Users, $this->id);
            $this->Scheduler = new Scheduler($this->Entity);
        } else {
            throw new ImproperActionException('Bad endpoint!');
        }
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
     * @apiSuccess {String} body Main content
     * @apiSuccess {String} category Item type
     * @apiSuccess {Number} category_id Id of the item type
     * @apiSuccess {String} color Hexadecimal color code for the item type
     * @apiSuccess {Number} date Date in YYYYMMDD format
     * @apiSuccess {String} fullname Name of the owner of the experiment
     * @apiSuccess {Number} has_attachment Number of files attached
     * @apiSuccess {Number} id Id of the item
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
     * @apiSuccess {String} body Main content
     * @apiSuccess {String} category Status
     * @apiSuccess {Number} category_id Id of the status
     * @apiSuccess {String} color Hexadecimal color code for the status
     * @apiSuccess {Number} date Date in YYYYMMDD format
     * @apiSuccess {DateTime} datetime Date and time when the experiment was created
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

    /**
     * Get experiment or item, one or several
     *
     * @return Response
     */
    private function getEntity(): Response
    {
        if ($this->id === null) {
            $DisplayParams = new DisplayParams();
            $DisplayParams->adjust($this->App);
            // default DisplayParams is 16, crank it up to 9000
            // in the future maybe use limit/offset/page query params
            $DisplayParams->limit = 9000;
            return new JsonResponse($this->Entity->readShow($DisplayParams, true));
        }
        $this->Entity->canOrExplode('read');
        // add the uploaded files
        $this->Entity->entityData['uploads'] = $this->Entity->Uploads->readAll();
        // add the linked items
        $this->Entity->entityData['links'] = $this->Entity->Links->read();
        // add the steps
        $this->Entity->entityData['steps'] = $this->Entity->Steps->read();

        return new JsonResponse($this->Entity->entityData);
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
        $Zip = new MakeBackupZip($this->Entity, $this->param);
        $Response = new StreamedResponse();
        $Response->setCallback(function () use ($Zip) {
            $Zip->getZip();
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
     *
     * @return Response
     */
    private function getEvents(): Response
    {
        // return all events if there is no id
        if ($this->id === null) {
            // TODO allow filtering of this through sent data
            return new JsonResponse($this->Scheduler->readAllFromTeam('2018-12-23T00:00:00 01:00', '2119-12-23T00:00:00 01:00'));
        }
        $this->Scheduler->setId($this->id);
        return new JsonResponse($this->Scheduler->readFromId());
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
     *
     * @return Response
     */
    private function getUpload(): Response
    {
        if ($this->id === null) {
            return new Response('You need to specify an ID!', 400);
        }
        $Uploads = new Uploads(new Experiments($this->Users));
        $uploadData = $Uploads->readFromId($this->id);
        // check user owns the file
        // we could also check if user has read access to the item
        // but for now let's just restrict downloading file via API to owned files
        if ((int) $uploadData['userid'] !== (int) $this->Users->userData['userid']) {
            return new Response('You do not have permission to access this resource.', 403);
        }
        $filePath = \dirname(__DIR__, 2) . '/uploads/' . $uploadData['long_name'];
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
     * @apiSuccess {String[]} None list of items_types for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *         [
     *           {
     *             "category_id": "1",
     *             "category": "Project",
     *             "color": "32a100",
     *             "bookable": "0",
     *             "template": "Some text",
     *             "ordering": "1"
     *           },
     *           {
     *             "category_id": "2",
     *             "category": "Microscope",
     *             "color": "2000eb",
     *             "bookable": "1",
     *             "template": "Template text",
     *             "ordering": "2"
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
     * @apiSuccess {String[]} None list of status for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/2 200 OK
     *     {
     *         [
     *           {
     *             "category_id": "1",
     *             "category": "Running",
     *             "color": "3360ff",
     *             "is_timestampable": "1",
     *             "is_default": "1"
     *           },
     *           {
     *             "category_id": "2",
     *             "category": "Success",
     *             "color": "54aa08",
     *             "is_timestampable": "1",
     *             "is_default": "0"
     *             }
     *         ]
     *     }
     */

    /**
     * Get items_types or status list for current team
     *
     * @return Response
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

    /**
     * Create an experiment
     *
     * @return Response
     */
    private function createExperiment(): Response
    {
        if ($this->Entity instanceof Database) {
            return new Response('Creating database items is not supported.', 400);
        }
        $params = new ParamsProcessor(array('id' => 0));
        $id = $this->Entity->create($params);
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

    /**
     * Create a database item
     *
     * @return Response
     */
    private function createItem(): Response
    {
        // check that the id we have is a valid item type from our team
        $ItemsTypes = new ItemsTypes($this->Users);
        $itemsTypesArr = $ItemsTypes->readAll();
        $validIds = array();
        foreach ($itemsTypesArr as $itemsTypes) {
            $validIds[] = $itemsTypes['category_id'];
        }
        if (!\in_array((string) $this->id, $validIds, true)) {
            return new Response('Cannot create an item with an item type id not in your team!', 403);
        }

        if ($this->id === null) {
            return new Response('Invalid id', 400);
        }
        $params = new ParamsProcessor(array('id' => $this->id));
        $id = $this->Entity->create($params);
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

    /**
     * Create link from experiment to item
     *
     * @return Response
     */
    private function createLink(): Response
    {
        $this->Entity->Links->create(new ParamsProcessor(array('id' => (int) $this->Request->request->get('link'))));
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

    /**
     * Create tag
     *
     * @return Response
     */
    private function createTag(): Response
    {
        $this->Entity->Tags->create(new ParamsProcessor(array('tag' => $this->Request->request->get('tag') ?? '')));
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /events/:id Create event
     * @apiName AddEvent
     * @apiGroup Events
     * @apiDescription Create an event in the scheduler for an item
     * @apiParam {String} start Start time
     * @apiParam {Number} end End time
     * @apiParam {String} title Comment for the booking
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # book database item 42 on the 30th of November 2019 from noon to 2pm
     * params = {
     *     "start": "2019-11-30T12:00:00",
     *     "end": "2019-11-30T14:00:00",
     *     "title": "Booked from API",
     * }
     * print(manager.create_event(42))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # book database item 42 on the 30th of November 2019 from noon to 2pm
     * curl -X POST -F "start=2019-11-30T12:00:00" -F "end=2019-11-30T14:00:00" -F "title=Booked from API" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/events/42
     * @apiSuccess {String} result Success
     * @apiSuccess {String} id Id of new event
     * @apiError {Number} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "start": "2019-11-30T12:00:00",
     *       "end": "2019-11-30T14:00:00",
     *       "title": "Booked from API"
     *     }
     */

    /**
     * Create an event in the scheduler for an item
     *
     * @return Response
     */
    private function createEvent(): Response
    {
        if ($this->id === null) {
            throw new ImproperActionException('Item id missing!');
        }
        $this->Entity->setId($this->id);
        $id = $this->Scheduler->create(
            $this->Request->request->get('start') ?? '',
            $this->Request->request->get('end') ?? '',
            $this->Request->request->get('title') ?? '',
        );
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

    /**
     * Delete an event
     *
     * @return Response
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
     * @apiExample {python} Python example
     * import elabapy
     * manager = elabapy.Manager(endpoint="https://elab.example.org/api/v1/", token="3148")
     * # update experiment 42
     * params = { "title": "New title", "date": "20200504", "body": "New body content" }
     * print(manager.post_experiment(42, params))
     * # update database item 42
     * print(manager.post_item(42, params))
     * @apiExample {shell} Curl example
     * export TOKEN="3148"
     * # update experiment 42
     * curl -X POST -F "title=a new title" -F "body=a new body" -F "date=20200504" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/experiments/42
     * # update database item 42
     * curl -X POST -F "title=a new title" -F "body=a new body" -F "date=20200504" -H "Authorization: $TOKEN" https://elab.example.org/api/v1/items/42
     * @apiSuccess {String} result Success
     * @apiError {String} error Error message
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "body": "New body to be updated.",
     *       "date": "20180308",
     *       "title": "New title"
     *     }
     */

    /**
     * Update experiment or item (title, date and body)
     *
     * @return Response
     */
    private function updateEntity(): Response
    {
        $this->Entity->update(
            $this->Request->request->get('title') ?? 'Untitled',
            $this->Request->request->get('date') ?? '',
            $this->Request->request->get('body') ?? '',
        );
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

    /**
     * Update experiment or item (title, date and body)
     *
     * @return Response
     */
    private function updateCategory(): Response
    {
        $this->Entity->updateCategory((int) $this->Request->request->get('category'));
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
     * with open('your-file.jpg', 'r') as myfile:
     *     params = { 'file': myfile }
     *     print(manager.upload_to_experiment(42, params))
     * # upload your-file.jpg to database item 1337
     * with open('your-file.jpg', 'r') as myfile:
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

    /**
     * Upload a file to an entity
     *
     * @return Response
     */
    private function uploadFile(): Response
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->Uploads->create($this->Request);

        return new JsonResponse(array('result' => 'success'));
    }
}
