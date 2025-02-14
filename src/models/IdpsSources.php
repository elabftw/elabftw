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

use DOMDocument;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\Url2Xml;
use Elabftw\Services\Xml2Idps;
use Elabftw\Traits\SetIdTrait;
use GuzzleHttp\Client;
use Override;
use PDO;

/**
 * For IDPS sources: .xml urls
 */
class IdpsSources extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        if ($this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('Only a Sysadmin can access this!');
        }
        $this->setId($id);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['url']);
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($this->id === null) {
            throw new ImproperActionException('No id was set!');
        }
        return match ($action) {
            // currently only one aspect is modifiable, the auto_refresh
            Action::Update => $this->toggleAutoRefresh(),
            Action::Replace => (
                function () {
                    $source = $this->readOne();
                    $Config = Config::getConfig();
                    $getter = new HttpGetter(new Client(), $Config->configArr['proxy']);
                    $Url2Xml = new Url2Xml($getter, $source['url'], new DOMDocument());
                    $dom = $Url2Xml->getXmlDocument();
                    $Xml2Idps = new Xml2Idps($dom);
                    $Idps = new Idps($this->requester);
                    return $this->refresh($Xml2Idps, $Idps);
                }
            )(),
            Action::Validate => $this->toggleEnable(1),
            Action::Finish => $this->toggleEnable(0),
            default => throw new ImproperActionException('Incorrect action parameter'),
        };
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/idps_sources/%s', $this->id ?? '');
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT idps_sources.id, idps_sources.url, idps_sources.auto_refresh,
            idps_sources.last_fetched_at, COALESCE(COUNT(idps.id), 0) AS idps_count,
            CAST(COALESCE(SUM(CASE WHEN idps.enabled = 1 THEN 1 ELSE 0 END), 0) AS UNSIGNED) AS idps_count_enabled
            FROM idps_sources LEFT JOIN idps ON idps_sources.id = idps.source GROUP BY idps_sources.id ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readAllAutoRefreshable(): array
    {
        $sql = 'SELECT idps_sources.id, idps_sources.url
            FROM idps_sources WHERE auto_refresh = 1';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT idps_sources.id, idps_sources.url, idps_sources.auto_refresh,
            idps_sources.last_fetched_at, COALESCE(COUNT(idps.id), 0) AS idps_count,
            CAST(COALESCE(SUM(CASE WHEN idps.enabled = 1 THEN 1 ELSE 0 END), 0) AS UNSIGNED) AS idps_count_enabled
            FROM idps_sources
            LEFT JOIN idps ON (idps_sources.id = idps.source) WHERE idps_sources.id = :id GROUP BY idps_sources.id, idps_sources.url, idps_sources.last_fetched_at ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM idps_sources WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        // also delete all idps with that source
        $sql = 'DELETE FROM idps WHERE source = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function create(string $url): int
    {
        $sql = 'INSERT INTO idps_sources (url) VALUES (:url)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':url', $url);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function refresh(Xml2Idps $Xml2Idps, Idps $Idps): array
    {
        $Idps->upsert($this->id ?? 0, $Xml2Idps);
        $this->touch();
        return $this->readOne();
    }

    private function toggleAutoRefresh(): array
    {
        $sql = 'UPDATE idps_sources SET auto_refresh = auto_refresh XOR 1 WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }

    private function toggleEnable(int $enabled): array
    {
        $Idps = new Idps($this->requester);
        $Idps->toggleEnabledFromSource($this->id ?? -1, $enabled);
        return $this->readOne();
    }

    private function touch(): bool
    {
        $sql = 'UPDATE idps_sources SET last_fetched_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
