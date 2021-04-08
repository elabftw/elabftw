<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\UpdatableInterface;
use Elabftw\Services\Filter;
use PDO;

class Metadata implements UpdatableInterface
{
    private AbstractEntity $Entity;

    private Db $Db;

    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }

    public function read(): string
    {
        $this->Entity->canOrExplode('read');
        $metadata = $this->Entity->getMetadata();
        if (empty($metadata)) {
            throw new ResourceNotFoundException('No metadata found!');
        }
        return $metadata;
    }

    /**
     * Update the whole json in metadata column
     */
    public function update(ContentParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('write');
        $sql = 'UPDATE ' . $this->Entity->getTable() . ' SET metadata = :metadata WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':metadata', $params->getContent());
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update only one field in the metadata json
     */
    public function updateExtraField(string $field, string $value): bool
    {
        $this->Entity->canOrExplode('write');
        // build field (input is double quoted to allow for whitespace in key)
        $field = '$.extra_fields."' . Filter::sanitize($field) . '".value';
        $value = Filter::sanitize($value);
        $sql = 'UPDATE ' . $this->Entity->getTable() . ' SET metadata = JSON_SET(metadata, :field, :value) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':field', $field);
        $req->bindParam(':value', $value);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
