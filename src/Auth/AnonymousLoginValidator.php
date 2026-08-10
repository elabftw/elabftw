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
use PDO;

final readonly class AnonymousLoginValidator
{
    public function __construct(
        private bool $isAnonymousAllowed,
    ) {}

    public function validate(int $teamId): void
    {
        if (!$this->isAnonymousAllowed || $teamId < 1) {
            throw new UnauthorizedException();
        }

        $Db = Db::getConnection();
        $sql = 'SELECT 1 FROM teams WHERE id = :team_id';
        $req = $Db->prepare($sql);
        $req->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $Db->execute($req);

        if ($req->fetchColumn() === false) {
            throw new UnauthorizedException();
        }
    }
}
