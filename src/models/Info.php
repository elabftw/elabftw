<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use PDO;

/**
 * Display information about the instance
 */
class Info implements RestInterface
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('No POST action');
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action.');
    }

    public function getPage(): string
    {
        return 'api/v2/info/';
    }

    public function readAll(): array
    {
        $Config = Config::getConfig();
        $base = array(
            'elabftw_version' => App::INSTALLED_VERSION,
            'ts_balance' => (int) $Config->configArr['ts_balance'],
            'ts_limit' => (int) $Config->configArr['ts_limit'],
        );
        return array_merge($base, $this->getAllStats());
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function destroy(): bool
    {
        throw new ImproperActionException('No DELETE action.');
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
