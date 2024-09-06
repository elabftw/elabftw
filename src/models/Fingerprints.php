<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\BaseQueryParams;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\Orderby;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\HttpGetter;
use Elabftw\Traits\SetIdTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fingerprints for molecules
 */
class Fingerprints implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(private HttpGetter $httpGetter, private string $fpaasUrl, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/fingerprints/%d', $this->id ?? 0);
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM fingerprints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    // TODO have optional param for readall that is a base query param interface
    public function readAll(): array
    {
        $queryParams = new BaseQueryParams(Request::createFromGlobals());
        $queryParams->orderby = Orderby::CreatedAt;
        $sql = 'SELECT * FROM fingerprints';
        $sql .= $queryParams->getSql();
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        //$this->update(new CommentParam($params['comment']));
        return $this->readOne();
    }

    public function calculate(string $mol): array
    {
        $res = $this->httpGetter->postJson($this->fpaasUrl, array('fmt' => 'smi', 'data' => $mol));
        return json_decode($res, true, 42);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create((int) $reqBody['itemId'], $reqBody['fp']);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM fingerprints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function search(array $fp): array
    {
        $sql = 'SELECT item_id FROM fingerprints WHERE 1=1';
        foreach ($fp as $key => $value) {
            if ($value == 0) {
                continue;
            }
            $sql .= sprintf(' AND fp%d & %d = %d', $key, $value, $value);
        }
        $req = $this->Db->prepare($sql . ' LIMIT 2');
        $req->execute();
        return $req->fetchAll();
    }

    public function create(int $itemId, array $fp): int
    {
        $sql = 'INSERT INTO fingerprints (item_id,';
        for ($i = 0; $i < 32; $i++) {
            $sql .= sprintf('fp%d,', $i);
        }
        $sql = rtrim($sql, ',');
        $sql .= ') VALUES(:item_id,';
        for ($i = 0; $i < 32; $i++) {
            $sql .= sprintf(':fp%d,', $i);
        }
        $sql = rtrim($sql, ',');
        $sql .= ')';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        for ($i = 0; $i < 32; $i++) {
            $req->bindParam(":fp$i", $fp[$i], PDO::PARAM_INT);
        }

        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }
}
