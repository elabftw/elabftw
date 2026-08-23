<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\State;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Services\Check;
use PDO;

final readonly class AccessKeyDownloadValidator
{
    public function validate(
        string $accessKey,
        string $longName,
    ): int {
        $accessKey = Check::accessKey($accessKey);

        $Db = Db::getConnection();

        $sql = "SELECT COALESCE(experiments.team, items.team, experiments_templates.team, items_types.team)
            FROM uploads
            LEFT JOIN experiments
                ON uploads.type = 'experiments'
                AND uploads.item_id = experiments.id
                AND experiments.state != :experiments_deleted
            LEFT JOIN experiments_templates
                ON uploads.type = 'experiments_templates'
                AND uploads.item_id = experiments_templates.id
                AND experiments_templates.state != :experiments_templates_deleted
            LEFT JOIN items
                ON uploads.type = 'items'
                AND uploads.item_id = items.id
                AND items.state != :items_deleted
            LEFT JOIN items_types
                ON uploads.type = 'items_types'
                AND uploads.item_id = items_types.id
                AND items_types.state != :items_types_deleted
            WHERE (
                uploads.long_name = :long_name
                OR CONCAT(uploads.long_name, '_th.jpg') = :thumbnail_name
            )
            AND COALESCE(
                experiments.access_key,
                items.access_key,
                experiments_templates.access_key,
                items_types.access_key
            ) = :access_key
            LIMIT 1";

        $req = $Db->prepare($sql);
        $req->bindValue(':long_name', $longName);
        $req->bindValue(':thumbnail_name', $longName);
        $req->bindValue(':access_key', $accessKey);
        $req->bindValue(':experiments_deleted', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':experiments_templates_deleted', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':items_deleted', State::Deleted->value, PDO::PARAM_INT);
        $req->bindValue(':items_types_deleted', State::Deleted->value, PDO::PARAM_INT);

        $Db->execute($req);

        $teamId = (int) $req->fetchColumn();

        if ($teamId < 1) {
            throw new UnauthorizedException();
        }

        return $teamId;
    }
}
