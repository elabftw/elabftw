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

use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use PDO;

use function _;

final class UserLoginValidator
{
    private Db $Db;

    public function validate(
        Authentication $authentication,
        int $teamId,
    ): UserLoginContext {
        $this->Db = Db::getConnection();

        // TODO move this valid_until param into users2teams table, maybe also validated param, too
        $sql = "SELECT
            IFNULL(users.valid_until, '3000-01-01') > NOW() AS is_valid,
            users.validated = 1 AS is_validated,
            users2teams.users_id IS NOT NULL AS is_member,
            COALESCE(users2teams.is_archived, 0) AS is_archived
            FROM users
            LEFT JOIN users2teams
                ON users2teams.users_id = users.userid
                AND users2teams.teams_id = :team_id
            WHERE users.userid = :userid
            LIMIT 1";

        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $authentication->userid, PDO::PARAM_INT);
        $req->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $this->Db->execute($req);

        $user = $req->fetch();

        if ($user === false || !(bool) $user['is_member']) {
            throw new UnauthorizedException();
        }

        if ((bool) $user['is_archived']) {
            throw new ImproperActionException(_('This account is archived in this team and cannot login.'));
        }

        if (!(bool) $user['is_valid']) {
            throw new ImproperActionException(_('Your account has expired. Contact your team Admin to extend its validity.'));
        }

        if (!(bool) $user['is_validated']) {
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        }

        return new UserLoginContext(
            $authentication->userid,
            $teamId,
            $authentication->method,
        );
    }
}
