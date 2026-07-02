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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

use function sprintf;

/**
 * Handle the users to ror relationship. Submodel for users.
 */
final class Users2Rors extends Abstract2Rors
{
    public function __construct(
        private readonly int $userid,
        bool $canwrite = false,
        ?string $ror = null,
    ) {
        parent::__construct($canwrite, $ror);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/users/%d/rors/', $this->userid);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return $this->selectAll($this->userid);
    }

    public function readAllFromId(int $userid): array
    {
        return $this->selectAll($userid);
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM users2rors WHERE users_id = :users_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canwriteOrExplode();
        $sql = 'DELETE FROM users2rors WHERE users_id = :users_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        return $this->Db->execute($req);
    }

    #[Override]
    protected function create(): int
    {
        if ($this->ror === null) {
            throw new ImproperActionException('Missing ROR value in URL');
        }
        $sql = 'INSERT IGNORE INTO users2rors (`users_id`, `ror`) VALUES (:users_id, :ror);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);

        return 1;
    }

    private function selectAll(int $userid): array
    {
        $sql = 'SELECT * FROM users2rors WHERE users_id = :users_id ORDER BY created_at ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();

    }
}
