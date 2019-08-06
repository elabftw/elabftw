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
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API requests
 */
class ApiController implements ControllerInterface
{
    /** @var Request $Request instance of Request */
    private $Request;

    /** @var AbstractEntity $Entity instance of Entity */
    private $Entity;

    /** @var Users $Users the authenticated user */
    private $Users;

    /** @var array $allowedMethods allowed HTTP methods */
    private $allowedMethods = array('GET', 'POST');

    /** @var bool $canWrite can we do POST methods? */
    private $canWrite = false;

    /** @var int|null $id the id at the end of the url */
    private $id;

    /** @var string $endpoint experiments, items or uploads */
    private $endpoint;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->Request = $request;
        $this->parseReq();
    }

    /**
     * Get Response from Request
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        // Check the HTTP method is allowed
        if (!\in_array($this->Request->server->get('REQUEST_METHOD'), $this->allowedMethods, true)) {
            // send error 405 for Method Not Allowed, with Allow header as per spec:
            // https://tools.ietf.org/html/rfc7231#section-7.4.1
            return new Response('Invalid HTTP request method!', 405, array('Allow' => \implode(', ', $this->allowedMethods)));
        }

        // Check if the Authorization Token was sent along
        if (!$this->Request->server->has('HTTP_AUTHORIZATION')) {
            // send error 401 if it's lacking an Authorization header, with WWW-Authenticate header as per spec:
            // https://tools.ietf.org/html/rfc7235#section-3.1
            return new Response('No access token provided!', 401, array('WWW-Authenticate' => 'Bearer'));
        }

        // GET UPLOAD
        if ($this->endpoint === 'uploads') {
            return $this->getUpload();
        }

        // GET ENTITY
        if ($this->Request->server->get('REQUEST_METHOD') === 'GET') {
            return $this->getEntity($this->id);
        }

        // POST request

        // POST means write access for the access token
        if (!$this->canWrite) {
            return new Response('Cannot use readonly key with POST method!', 403);
        }
        // FILE UPLOAD
        if ($this->Request->files->count() > 0) {
            return $this->uploadFile();
        }

        // TITLE DATE BODY UPDATE
        if ($this->Request->request->has('title')) {
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

        // CREATE AN EXPERIMENT
        return $this->createExperiment();
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

        // assign the endpoint (experiments, items, uploads)
        $this->endpoint = array_shift($args) ?? '';

        // verify the key and load user info
        $Users = new Users();
        $ApiKeys = new ApiKeys($Users);
        $keyArr = $ApiKeys->readFromApiKey($this->Request->server->get('HTTP_AUTHORIZATION'));
        $Users->populate((int) $keyArr['userid']);
        $this->Users = $Users;
        $this->canWrite = (bool) $keyArr['canWrite'];

        // load Entity
        // if endpoint is uploads we don't actually care about the entity type
        if ($this->endpoint === 'experiments' || $this->endpoint === 'uploads') {
            $this->Entity = new Experiments($Users, $this->id);
        } elseif ($this->endpoint === 'items') {
            $this->Entity = new Database($Users, $this->id);
        } else {
            throw new ImproperActionException('Bad endpoint!');
        }
    }

    /**
     * @apiDefine GetEntity
     * @apiParam {Number} id Entity id
     */

    /**
     * @api {get} /items/:id Read a database item
     * @apiName GetItem
     * @apiGroup Entity
     * @apiParam {Number} id Entity id
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
     * @api {get} /experiments/:id Read an experiment
     * @apiName GetExperiment
     * @apiGroup Entity
     * @apiParam {Number} id Entity id
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
     * @apiSuccess {String} visibility Visibility of the experiment
     *
     */

    /**
     * Get experiment or item, one or several
     *
     * @param int|null $id id of the entity
     * @return Response
     */
    private function getEntity(?int $id): Response
    {
        if ($id === null) {
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
        if ((int) $uploadData['userid'] !== $this->Users->userData['userid']) {
            return new Response('You do not have permission to access this resource.', 403);
        }
        $filePath = \dirname(__DIR__, 2) . '/uploads/' . $uploadData['long_name'];
        return new BinaryFileResponse($filePath);
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
        return new JsonResponse(array('id' => $id));
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
