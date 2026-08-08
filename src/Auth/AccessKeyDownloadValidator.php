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
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Services\Check;

final readonly class AccessKeyDownloadValidator
{
    public function validate(
        string $accessKey,
        string $longName,
    ): int {
        $accessKey = Check::accessKey($accessKey);

        $Db = Db::getConnection();

        $sql = "SELECT COALESCE(experiments.team, items.team)
            FROM uploads
            LEFT JOIN experiments
                ON uploads.type = 'experiments'
                AND uploads.item_id = experiments.id
            LEFT JOIN items
                ON uploads.type = 'items'
                AND uploads.item_id = items.id
            WHERE (
                uploads.long_name = :long_name
                OR CONCAT(uploads.long_name, '_th.jpg') = :thumbnail_name
            )
            AND COALESCE(
                experiments.access_key,
                items.access_key
            ) = :access_key
            LIMIT 1";

        $req = $Db->prepare($sql);
        $req->bindValue(
            ':long_name',
            $longName,
        );
        $req->bindValue(
            ':thumbnail_name',
            $longName,
        );
        $req->bindValue(
            ':access_key',
            $accessKey,
        );

        $Db->execute($req);

        $teamId = (int) $req->fetchColumn();

        if ($teamId < 1) {
            throw new UnauthorizedException();
        }

        return $teamId;
    }
}
