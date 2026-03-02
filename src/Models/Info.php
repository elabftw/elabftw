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
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Override;
use PDO;

/**
 * Display information about the instance
 */
final class Info extends AbstractRest
{
    private const int DEFAULT_HIST_BUCKET_SIZE = 120;

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/info/';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        if ($queryParams && $queryParams->getQuery()->has('hist')) {
            $columns = $queryParams->getQuery()->has('columns') ? $queryParams->getQuery()->getInt('columns') : null;
            return $this->hist($columns);
        }
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
     * Gather data for histogram display: numBuckets is the number of columns we want
     */
    public function hist(?int $numBuckets = null): array
    {
        $sql = "WITH
          anchor AS (
            SELECT DATE(MIN(created_at)) AS min_date
            FROM (
              SELECT created_at FROM experiments
              UNION ALL
              SELECT created_at FROM items
              UNION ALL
              SELECT created_at FROM users
            ) t
          ),
          params AS (
            SELECT
              min_date,
              GREATEST(
                1,
                CEIL( (DATEDIFF(CURDATE(), min_date) + 1) / :num_buckets )
              ) AS bucket_size
            FROM anchor
          ),

          exp_raw AS (
            SELECT
              DATE(
                (SELECT min_date FROM params)
                + INTERVAL FLOOR(
                    DATEDIFF(created_at, (SELECT min_date FROM params))
                    / (SELECT bucket_size FROM params)
                  ) * (SELECT bucket_size FROM params) DAY
              ) AS bucket_start,
              COUNT(*) AS bucket_count
            FROM experiments
            GROUP BY bucket_start
          ),
          exp AS (
            SELECT
              bucket_start,
              SUM(bucket_count) OVER (ORDER BY bucket_start) AS total
            FROM exp_raw
          ),

          it_raw AS (
            SELECT
              DATE(
                (SELECT min_date FROM params)
                + INTERVAL FLOOR(
                    DATEDIFF(created_at, (SELECT min_date FROM params))
                    / (SELECT bucket_size FROM params)
                  ) * (SELECT bucket_size FROM params) DAY
              ) AS bucket_start,
              COUNT(*) AS bucket_count
            FROM items
            GROUP BY bucket_start
          ),
          it AS (
            SELECT
              bucket_start,
              SUM(bucket_count) OVER (ORDER BY bucket_start) AS total
            FROM it_raw
          ),

          u_raw AS (
            SELECT
              DATE(
                (SELECT min_date FROM params)
                + INTERVAL FLOOR(
                    DATEDIFF(created_at, (SELECT min_date FROM params))
                    / (SELECT bucket_size FROM params)
                  ) * (SELECT bucket_size FROM params) DAY
              ) AS bucket_start,
              COUNT(*) AS bucket_count
            FROM users
            GROUP BY bucket_start
          ),
          u AS (
            SELECT
              bucket_start,
              SUM(bucket_count) OVER (ORDER BY bucket_start) AS total
            FROM u_raw
          )

        SELECT JSON_OBJECT(
          'experiments',
            (SELECT JSON_ARRAYAGG(j.obj)
             FROM (
               SELECT JSON_OBJECT('bucket_start', bucket_start, 'total', total) AS obj
               FROM exp
               ORDER BY bucket_start
             ) j),
          'items',
            (SELECT JSON_ARRAYAGG(j.obj)
             FROM (
               SELECT JSON_OBJECT('bucket_start', bucket_start, 'total', total) AS obj
               FROM it
               ORDER BY bucket_start
             ) j),
          'users',
            (SELECT JSON_ARRAYAGG(j.obj)
             FROM (
               SELECT JSON_OBJECT('bucket_start', bucket_start, 'total', total) AS obj
               FROM u
               ORDER BY bucket_start
             ) j)
        ) AS data;";

        $req = $this->Db->prepare($sql);
        $numBuckets ??= self::DEFAULT_HIST_BUCKET_SIZE;
        // avoid division by zero
        if ($numBuckets < 1) {
            $numBuckets = self::DEFAULT_HIST_BUCKET_SIZE;
        }
        $req->bindParam(':num_buckets', $numBuckets, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        return json_decode($res['data'], true, 4);
    }

    /**
     * Get statistics for the whole install
     */
    private function getAllStats(): array
    {
        $sql = 'SELECT
        (SELECT COUNT(users.userid) FROM users) AS all_users_count,
        (SELECT COUNT(DISTINCT u2t.users_id) FROM users2teams AS u2t INNER JOIN users ON (users.userid = u2t.users_id AND u2t.is_archived = 0 AND users.validated = 1)) AS active_users_count,
        (SELECT COUNT(items.id) FROM items) AS items_count,
        (SELECT COUNT(teams.id) FROM teams) AS teams_count,
        (SELECT COUNT(compounds.id) FROM compounds) AS compounds_count,
        (SELECT COUNT(experiments.id) FROM experiments) AS experiments_count,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.timestamped = 1) AS experiments_timestamped_count,
        (SELECT COUNT(id) FROM uploads WHERE comment LIKE "Timestamp archive%" = 1 AND created_at > (NOW() - INTERVAL 1 MONTH)) AS entities_timestamped_count_last_30_days';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $res = $req->fetch(PDO::FETCH_NAMED);
        if ($res === false) {
            return array();
        }

        return $res;
    }
}
