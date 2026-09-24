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
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use PDO;

use function is_int;

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
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE access_key = :ak AND state != :deleted';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':ak', $ak);
        $req->bindValue(':deleted', State::Deleted->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function toggleAccessKey(): ?string
    {
        $accessKey = $this->getAccessKey() === null ? Tools::getUuidv4() : null;
        $sql = 'UPDATE ' . $this->entityType->value . ' SET access_key = :access_key WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $pdoType = $accessKey === null ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $req->bindValue(':access_key', $accessKey, $pdoType);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->getAccessKey();
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
