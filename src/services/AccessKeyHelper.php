<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use PDO;

/**
 * A utility class to deal with access key stuff
 */
final class AccessKeyHelper
{
    private Db $Db;

    public function __construct(private EntityType $entityType, private ?int $id = null)
    {
        $this->Db = Db::getConnection();
    }

    public function getIdFromAccessKey(string $ak): int
    {
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE access_key = :ak';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':ak', $ak);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function toggleAccessKey(): ?string
    {
        $sql = 'UPDATE ' . $this->entityType->value . ' SET access_key = ' . $this->getSqlValue() . ' WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->getAccessKey();
    }

    private function getSqlValue(): string
    {
        if ($this->getAccessKey() === null) {
            return 'UUID()';
        }
        return 'NULL';
    }

    private function getAccessKey(): ?string
    {
        $sql = 'SELECT access_key FROM ' . $this->entityType->value . ' WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false || is_int($res)) {
            return null;
        }
        return $res;
    }
}
