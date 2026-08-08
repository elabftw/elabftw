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
        $this->checkAccountValidity($authentication->userid);

        return new UserLoginContext(
            $authentication->userid,
            $teamId,
            $authentication->method,
        );
    }

    // TODO move this valid_until param into users2teams table, maybe also validated param, too
    private function checkAccountValidity(int $userid): void
    {
        $sql = "SELECT
            IFNULL(valid_until, '3000-01-01') > NOW() AS is_valid,
            validated = 1 AS is_validated
            FROM users
            WHERE userid = :userid";

        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $user = $req->fetch();

        if (!$user || !(bool) $user['is_valid']) {
            throw new ImproperActionException(
                _('Your account has expired. Contact your team Admin to extend its validity.'),
            );
        }

        if (!(bool) $user['is_validated']) {
            throw new ImproperActionException(
                _('Your account is not validated. An admin of your team needs to validate it!'),
            );
        }
    }
}
