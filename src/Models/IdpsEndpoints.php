<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\SamlBinding;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * For IDPS endpoints
 */
final class IdpsEndpoints extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $requester, public ?int $idpId = null, public ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/idps/%d/endpoints/%d', $this->idpId ?? 0, $this->id ?? 0);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->ensureIsSysadmin();
        $sql = 'SELECT * FROM idps_endpoints WHERE idp = :idp';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':idp', $this->idpId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        foreach ($res as &$endpoint) {
            $endpoint['binding_urn'] = SamlBinding::from($endpoint['binding'])->toUrn();
            $endpoint['service_type'] = $endpoint['is_slo'] ? 'slo' : 'sso';
        }
        return $res;
    }

    #[Override]
    public function readOne(): array
    {
        $this->ensureIsSysadmin();
        $sql = 'SELECT * FROM idps_endpoints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $this->Db->fetch($req);
        $res['binding_urn'] = SamlBinding::from($res['binding'])->toUrn();
        $res['service_type'] = $res['is_slo'] ? 'slo' : 'sso';
        return $res;
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->ensureIsSysadmin();
        if ($this->idpId === null) {
            throw new ImproperActionException('No IDP id provided!');
        }
        return $this->create(
            SamlBinding::tryFrom((int) ($reqBody['binding'] ?? 0)) ?? throw new ImproperActionException('Incorrect binding value'),
            $reqBody['location'],
            BinaryValue::tryFrom((int) ($reqBody['is_slo'] ?? 0)) ?? BinaryValue::False,
        );
    }

    #[Override]
    public function destroy(): bool
    {
        $this->ensureIsSysadmin();
        $sql = 'DELETE FROM idps_endpoints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function create(SamlBinding $binding, string $location, BinaryValue $isSlo = BinaryValue::False): int
    {
        $sql = 'INSERT IGNORE INTO idps_endpoints (idp, binding, location, is_slo)
            VALUES (:idp, :binding, :location, :is_slo)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':idp', $this->idpId, PDO::PARAM_INT);
        $req->bindValue(':binding', $binding->value);
        $req->bindParam(':location', $location);
        $req->bindValue(':is_slo', $isSlo->value);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    private function ensureIsSysadmin(): void
    {
        if ($this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('Only a Sysadmin can access this endpoint!');
        }
    }
}
