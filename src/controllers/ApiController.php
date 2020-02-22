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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
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
    /** @var Request $Request instance of Request */
    private $Request;

    /** @var AbstractCategory $Category instance of ItemsTypes or Status
     * @psalm-suppress PropertyNotSetInConstructor */
    private $Category;

    /** @var AbstractEntity $Entity instance of Entity
     * @psalm-suppress PropertyNotSetInConstructor */
    private $Entity;

    /** @var Scheduler $Scheduler instance of Scheduler
     * @psalm-suppress PropertyNotSetInConstructor */
    private $Scheduler;

    /** @var Users $Users the authenticated user */
    private $Users;

    /** @var array $allowedMethods allowed HTTP methods */
    private $allowedMethods = array('GET', 'POST', 'DELETE');

    /** @var bool $canWrite can we do POST methods? */
    private $canWrite = false;

    /** @var int|null $id the id at the end of the url */
    private $id;

    /** @var string $endpoint experiments, items or uploads */
    private $endpoint;

    /** @var string $param used by backupzip to get the period */
    private $param;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->Request = $request;
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
                /*
                if ($this->param === 'zip') {
                    return $this->getZip();
                }
                 */
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
        $args = explode('/', rtrim($this->Request->query->get('req'), '/'));

        // assign the id if there is one
        $id = null;
        if (Check::id((int) end($args)) !== false) {
            $id = (int) end($args);
        }
        $this->id = $id;

        // assign the endpoint (experiments, items, uploads, items_types, status)
        $this->endpoint = array_shift($args) ?? '';
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
            return new JsonResponse($this->Entity->read());
        }
        $this->Entity->canOrExplode('read');
        // add the uploaded files
        $this->Entity->entityData['uploads'] = $this->Entity->Uploads->readAll();
        // add the linked items
        $this->Entity->entityData['links'] = $this->Entity->Links->readAll();
        // add the steps
        $this->Entity->entityData['steps'] = $this->Entity->Steps->readAll();

        return new JsonResponse($this->Entity->entityData);
    }

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

    /*
    private function getZip(): Response
    {
        // no permission check here so for the moment only let a sysadmin get that
        // used for backups
        if (!$this->Users->userData['is_sysadmin']) {
            throw new IllegalActionException('Only a sysadmin can use this endpoint!');
        }
        $idList = $this->Entity->getIdFromLastchange($this->secondParam);
        // MakeStreamZip requires a space separated string
        $idList = implode(' ', $idList);
        $Zip = new MakeStreamZip($this->Entity, $idList);
        return new BinaryFileResponse($Zip->getZip());
    }
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
     * @apiExample {curl} Example usage:
     * curl -H "Authorization: $TOKEN" "https://elab.example.org/api/v1/events/2"
     * @apiSuccess {Number} id Id of the event
     * @apiSuccess {Number} team Id of the team
     * @apiSuccess {Number} item Booked item
     * @apiSuccess {String} start Start date/time
     * @apiSuccess {String} end End date/time
     * @apiSuccess {String} title Comment for the event
     * @apiSuccess {Number} userid Id of the user that booked it
     * @apiSuccessExample {json} Success-Response:
     * {
     *     "id":"2",
     *     "team":"1",
     *     "item":"105",
     *     "start":"2019-11-29T09:30:00",
     *     "end":"2019-11-29T14:30:00",
     *     "title":"ahaha",
     *     "userid":"1"
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
     * @apiSuccess {Binary} the file
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
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
     * @apiSuccess {String[]} list of items_types for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/1.1 200 OK
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
     * @apiSuccess {String[]} list of status for the team
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/1.1 200 OK
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
     * @apiSuccess {String} Id Id of the new experiment
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": "42"
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
        $id = $this->Entity->create(0);
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {post} /items/:id Create a database item
     * @apiName CreateItem
     * @apiGroup Entity
     * @apiSuccess {String} Id Id of the new item type (category)
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": "42"
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
        $id = $this->Entity->create($this->id);
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {post} /experiments/:id Add a link
     * @apiName AddLink
     * @apiGroup Entity
     * @apiParam {Number} id Entity id
     * @apiParam {Number} link Id of the database item to link to
     * @apiSuccess {String} Success or error message
     * @apiSuccessExample {Json} Success-Response:
     *     HTTP/1.1 200 OK
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
        if ($this->Entity instanceof Database) {
            return new Response('Creating database items is not supported.', 400);
        }
        $this->Entity->Links->create((int) $this->Request->request->get('link'));
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Add a tag
     * @apiName AddTag
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {String} tag Tag to add
     * @apiSuccess {String} result Success
     * @apiError {String} error Error mesage
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
        $this->Entity->Tags->create($this->Request->request->get('tag'));
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /events/:id Create event
     * @apiName AddEvent
     * @apiGroup Events
     * @apiDescription Create an event in the scheduler for an item
     * @apiParam {Sring} start Start time
     * @apiParam {Number} end End time
     * @apiParam {String} title Comment for the booking
     * @apiSuccess {String} result Success
     * @apiSuccess {String} id Id of new event
     * @apiError {Number} error Error mesage
     * @apiParamExample {Json} Request-Example:
     *     {
     *       "start": "2019-11-30T12:00:00",
     *       "end": "2019-11-30T14:00:00"
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
            $this->Request->request->get('start'),
            $this->Request->request->get('end'),
            $this->Request->request->get('title'),
        );
        return new JsonResponse(array('result' => 'success', 'id' => $id));
    }

    /**
     * @api {delete} /events/:id Destroy event
     * @apiName DestroyEvent
     * @apiGroup Events
     * @apiParam {Number} id Id of the event
     * @apiDescription Delete an event
     * @apiSuccess {String} result Success
     * @apiError {String} error Error mesage
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
     * @apiSuccess {String} result Success
     * @apiError {String} error Error mesage
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
            $this->Request->request->get('title'),
            $this->Request->request->get('date'),
            $this->Request->request->get('body')
        );
        return new JsonResponse(array('result' => 'success'));
    }

    /**
     * @api {post} /:endpoint/:id Upload a file
     * @apiName AddFile
     * @apiGroup Entity
     * @apiParam {String} endpoint 'experiments' or 'items'
     * @apiParam {Number} id Entity id
     * @apiParam {File} file File to upload
     * @apiSuccess {String} result Success
     * @apiError {String} error Error mesage
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
