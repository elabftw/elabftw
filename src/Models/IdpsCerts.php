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

use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\CertPurpose;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Filter;
use Elabftw\Services\Xml2Idps;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * For IDPS certificates
 */
final class IdpsCerts extends AbstractRest
{
    use SetIdTrait;

    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private Users $requester, public ?int $idpId = null, public ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/idps/%d/certs/%d', $this->idpId ?? 0, $this->id ?? 0);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'SELECT * FROM idps_certs WHERE idp = :idp';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':idp', $this->idpId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        foreach ($res as &$cert) {
            $cert['purpose_human'] = CertPurpose::from($cert['purpose'])->name;
        }
        return $res;
    }

    #[Override]
    public function readOne(): array
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'SELECT * FROM idps_certs WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $cert = $this->Db->fetch($req);
        $cert['purpose_human'] = CertPurpose::from($cert['purpose'])->name;
        return $cert;
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->requester->isSysadminOrExplode();
        if ($this->idpId === null) {
            throw new ImproperActionException('No IDP id provided!');
        }
        [$pem, $sha256, $notBefore, $notAfter] = Xml2Idps::processCert($reqBody['x509']);
        return $this->create(
            CertPurpose::tryFrom((int) ($reqBody['purpose'] ?? 0)) ?? CertPurpose::Signing,
            $pem,
            $sha256,
            $notBefore,
            $notAfter,
        );
    }

    public function sync(int $idpId, array $idp): bool
    {
        $this->idpId = $idpId;
        // if the cert already exists, it will simply "touch" it: update modified_at
        foreach ($idp['certs'] as $cert) {
            $this->create(
                $cert['purpose'],
                $cert['x509'],
                $cert['sha256'],
                $cert['not_before'],
                $cert['not_after'],
            );
        }
        // now we want to prune certs that are no longer in the source
        $allCerts = $this->readAll();
        $stayHashes = array_fill_keys(
            array_filter(
                array_map(
                    static fn(array $c) => $c['sha256'],
                    $idp['certs'] ?? array()
                )
            ),
            true
        );

        $certsToPrune = array_values(array_filter(
            $allCerts,
            static fn(array $dbCert) => !isset($stayHashes[$dbCert['sha256'] ?? ''])
        ));
        foreach ($certsToPrune as $cert) {
            $this->id = $cert['id'];
            $this->destroy();
        }

        return true;
    }

    #[Override]
    public function destroy(): bool
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'DELETE FROM idps_certs WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function create(CertPurpose $purpose, string $x509, string $sha256, DateTimeImmutable $notBefore, DateTimeImmutable $notAfter): int
    {
        $id = $this->findCertByHash($sha256, $purpose);
        if ($id === null) {
            $sql = 'INSERT INTO idps_certs (idp, purpose, x509, sha256, not_before, not_after)
                VALUES (:idp, :purpose, :x509, :sha256, :not_before, :not_after)';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':idp', $this->idpId, PDO::PARAM_INT);
            $req->bindValue(':purpose', $purpose->value);
            $req->bindValue(':x509', Filter::pem($x509));
            $req->bindParam(':sha256', $sha256);
            $req->bindValue(':not_before', $notBefore->format(self::DATETIME_FORMAT));
            $req->bindValue(':not_after', $notAfter->format(self::DATETIME_FORMAT));
            $this->Db->execute($req);
            return $this->Db->lastInsertId();
        }
        $this->id = $id;
        return $this->touch();
    }

    private function touch(): int
    {
        $sql = 'UPDATE idps_certs SET modified_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->id ?? 0;
    }

    private function findCertByHash(string $sha256, CertPurpose $purpose): ?int
    {
        $sql = 'SELECT id FROM idps_certs WHERE sha256 = :sha256 AND idp = :idp AND purpose = :purpose';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':sha256', $sha256);
        $req->bindValue(':idp', $this->idpId);
        $req->bindValue(':purpose', $purpose->value);
        $this->Db->execute($req);
        if ($req->rowCount() > 0) {
            return (int) $req->fetchColumn();
        }
        return null;
    }
}
