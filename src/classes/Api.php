<?php
/**
 * \Elabftw\Elabftw\Api
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * The REST API for eLabFTW
 *
 */
class Api
{
    /** @var AbstractEntity $Entity Experiments or Database */
    private $Entity;

    /**
     * Get data for user from the API key
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
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
     * @return array
     */
    public function createExperiment(): array
    {
        if ($this->Entity instanceof Experiments) {
            $id = $this->Entity->create();
            return array('id' => $id);
        }
        return array('error' => 'Unable to create experiment!');
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
     * Add a link to an experiment
     *
     * @param int $itemId Id of the database item to link to
     * @return array
     */
    public function addLink(int $itemId): array
    {
        if ($this->Entity instanceof Database) {
            return array('error' => 'Endpoint must be experiments not items!');
        }
        if ($this->Entity->id === null) {
            return array('error' => 'No ID set. Aborting!');
        }

        if ($this->Entity->Links->create($itemId)) {
            return array('result' => 'success');
        }

        return array('error' => 'Unable to add link!');
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
     * @apiSuccess {String} type See category_id
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
     * @apiSuccess {Number} status See category_id
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
     * Read an entity in full
     *
     * @return array<mixed, mixed>
     */
    public function getEntity(): array
    {
        if ($this->Entity->id === null) {
            return $this->Entity->read();
        }

        // now id is set
        $this->Entity->canOrExplode('read');
        // add the uploaded files
        $uploadedFilesArr = $this->Entity->Uploads->readAll();
        $this->Entity->entityData['uploads'] = $uploadedFilesArr;

        return $this->Entity->entityData;
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
     * Update an entity
     *
     * @param string $title Title
     * @param string $date Date
     * @param string $body Body
     * @return array{error?:string, result?:string}
     */
    public function updateEntity(string $title, string $date, string $body): array
    {
        $this->Entity->canOrExplode('write');

        if ($this->Entity->update($title, $date, $body)) {
            return array('result' => 'success');
        }

        return array('error' => Tools::error());
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
     * Add a tag to an entity
     *
     * @param string $tag
     * @return array{error?:string, result?:string}
     */
    public function addTag(string $tag): array
    {
        $this->Entity->canOrExplode('write');

        if ($this->Entity->Tags->create($tag)) {
            return array('result' => 'success');
        }

        return array('error' => Tools::error());
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
     * Add a file to an entity
     *
     * @param Request $request
     * @return array{error?:string, result?:string}
     */
    public function uploadFile(Request $request): array
    {
        $this->Entity->canOrExplode('write');

        if ($this->Entity->Uploads->create($request)) {
            return array('result' => 'success');
        }

        return array('error' => Tools::error());
    }
}
