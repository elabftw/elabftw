<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Check;
use Override;
use PDO;

use function sprintf;

/**
 * Handle the users to ror relationship. Submodel for users.
 */
final class Users2Rors extends AbstractRest
{
    public function __construct(private Users $requester, private Users $target, private ?string $ror = null)
    {
        $this->Db = Db::getConnection();
        if ($this->ror !== null) {
            Check::ror($this->ror);
        }
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/users/%d/rors/', $this->target->getUserid());
    }

    private function canWriteOrExplode(): void
    {
        if (!$this->requester->isAdminOf($this->target->getUserid())) {
            throw new IllegalActionException();
        }
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->canWriteOrExplode();
        return match ($action) {
            Action::Create => $this->create(),
            default => throw new ImproperActionException('Incorrect action for ROR.'),
        };
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT * FROM users2rors WHERE users_id = :users_id ORDER BY created_at ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->target->getUserid(), PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM users2rors WHERE users_id = :users_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->target->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM users2rors WHERE users_id = :users_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->target->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        return $this->Db->execute($req);
    }

    private function create(): int
    {
        if ($this->ror === null) {
            throw new ImproperActionException('Missing ROR value in URL');
        }
        $sql = 'INSERT IGNORE INTO users2rors (`users_id`, `ror`) VALUES (:users_id, :ror);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->target->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);

        return 1;
    }
}
