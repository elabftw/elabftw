<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\State;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

/**
 * Display information about the instance
 */
class Info extends AbstractRest
{
    public function getApiPath(): string
    {
        return 'api/v2/info/';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $Config = Config::getConfig();
        $Uploads = new Uploads(new Experiments(new Users()));
        $Uploads->readFilesizeSum();
        $base = array(
            'elabftw_version' => App::INSTALLED_VERSION,
            'elabftw_version_int' => App::INSTALLED_VERSION_INT,
            'ts_balance' => (int) $Config->configArr['ts_balance'],
            'ts_limit' => (int) $Config->configArr['ts_limit'],
            'uploads_filesize_sum' => $Uploads->readFilesizeSum(),
            'uploads_filesize_sum_formatted' => Tools::formatBytes($Uploads->readFilesizeSum()),
        );
        return array_merge($base, $this->getAllStats());
    }

    /**
     * Get statistics for the whole install
     */
    private function getAllStats(): array
    {
        $sql = 'SELECT
        (SELECT COUNT(users.userid) FROM users) AS all_users_count,
        (SELECT COUNT(users.userid) FROM users WHERE archived = 0 AND validated = 1) AS active_users_count,
        (SELECT COUNT(items.id) FROM items WHERE items.state = :state) AS items_count,
        (SELECT COUNT(teams.id) FROM teams) AS teams_count,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.state = :state) AS experiments_count,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.state = :state AND experiments.timestamped = 1) AS experiments_timestamped_count,
        (SELECT COUNT(id) FROM uploads WHERE comment LIKE "Timestamp archive%" = 1 AND created_at > (NOW() - INTERVAL 1 MONTH)) AS entities_timestamped_count_last_30_days';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch(PDO::FETCH_NAMED);
        if ($res === false) {
            return array();
        }

        return $res;
    }
}
