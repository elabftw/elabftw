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

use Elabftw\Enums\Orderby;
use Elabftw\Enums\State;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\BaseQueryParams;
use Override;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;

class UserUploads extends AbstractRest
{
    public function __construct(private Users $owner, private ?int $id = null)
    {
        parent::__construct();
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/user/%d/uploads/', $this->owner->userid ?? 'me');
    }

    public function getQueryParams(?InputBag $query = null): QueryParamsInterface
    {
        return new BaseQueryParams(query: $query, orderby: Orderby::CreatedAt, limit: 42);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        $idFilter = '';
        if ($this->id) {
            $idFilter = sprintf(' AND entity.id = %d', $this->id);
        }
        $sql = 'SELECT entity.id, entity.real_name, entity.long_name, entity.created_at, entity.filesize, entity.type, entity.comment, entity.state,
            entity.hash, entity.hash_algorithm, entity.storage, entity.immutable,
            COALESCE(experiments.id, items.id, experiments_templates.id) AS entity_id,
            COALESCE(experiments.title, items.title, experiments_templates.title) AS entity_title,
            CASE
                WHEN entity.type = "experiments" THEN "experiments"
                WHEN entity.type = "items" THEN "database"
                WHEN entity.type = "experiments_templates" THEN "ucp"
                ELSE ""
            END AS page
            FROM uploads As entity
            LEFT JOIN experiments ON (entity.item_id = experiments.id AND entity.type = "experiments")
            LEFT JOIN items ON (entity.item_id = items.id AND entity.type = "items")
            LEFT JOIN experiments_templates ON (entity.item_id = experiments_templates.id AND entity.type = "experiments_templates")
            WHERE entity.userid = :userid'
            . $idFilter . $queryParams->getSql();
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->owner->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function countAll(): int
    {
        $sql = 'SELECT COUNT(uploads.id) FROM uploads WHERE userid = :userid AND (state = :state_normal OR state = :state_archived)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->owner->userid, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }
}
