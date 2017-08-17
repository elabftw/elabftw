<?php
/**
 * \Elabftw\Elabftw\Api
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * The REST API for eLabFTW
 *
 */
class Api
{
    /** @var Entity $Entity Experiments or Database */
    private $Entity;

    /** @var Request $Request The request */
    private $Request;

    /** @var array $content the output */
    private $content;

    /** @var int $id the id of the entity */
    private $id = null;

    /**
     * Get data for user from the API key
     *
     * @param Request $request
     */
    public function __construct(Entity $entity)
    {
        $this->Entity = $entity;
    }

    /**
     * Return the response
     *
     * @return array
     *
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Create an experiment
     *
     * @return array
     */
    public function createExperiment()
    {
        $id = $this->Entity->create();

        return array('id' => $id);
    }

    /**
     * Read an entity in full
     *
     * @return array<string,array>
     */
    public function getEntity()
    {
        $Uploads = new Uploads($this->Entity);
        $uploadedFilesArr = $Uploads->readAll();
        $entityArr = $this->Entity->read();
        $entityArr['uploads'] = $uploadedFilesArr;

        return $entityArr;
    }

    /**
     * Update an entity
     *
     * @return string[]
     */
    public function updateEntity($title, $date, $body)
    {
        $this->Entity->canOrExplode('write');

        if ($this->Entity->update($title, $date, $body)) {
            return array('Result', 'Success');
        }

        return array('error', Tools::error());
    }

    /**
     * Add a file to an entity
     *
     * @return string[]
     */
    public function uploadFile(Request $request)
    {
        $this->Entity->canOrExplode('write');

        $Uploads = new Uploads($this->Entity);

        if ($Uploads->create($request)) {
            return array('Result', 'Success');
        }

        return array('Result', Tools::error());
    }
}
