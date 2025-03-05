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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use PDO;

final class Tags2Entity
{
    private Db $Db;

    public function __construct(private EntityType $entityType)
    {
        $this->Db = Db::getConnection();
    }

    public function getEntitiesIdFromTags(string $idOrTag, array $tags): array
    {
        // look for item ids that have all the tags not only one of them
        // the HAVING COUNT is necessary to make an AND search between tags
        // Note: we cannot use a placeholder for the IN of the tags because we need the quotes
        $inPlaceholders = implode(', ', array_map(
            fn($key): string => ":tag$key",
            array_keys($tags),
        ));
        $sql = 'SELECT tags2entity.item_id FROM `tags2entity`
            INNER JOIN (SELECT id FROM tags WHERE tags.' . $idOrTag . ' IN ( ' . $inPlaceholders . ' )) tg ON tags2entity.tag_id = tg.id
            WHERE tags2entity.item_type = :type GROUP BY item_id HAVING COUNT(DISTINCT tags2entity.tag_id) = :count';
        $req = $this->Db->prepare($sql);
        // bind the tags in IN clause
        foreach ($tags as $key => $tag) {
            $req->bindValue(":tag$key", $tag);
        }
        $req->bindValue(':type', $this->entityType->value);
        $req->bindValue(':count', count($tags), PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
