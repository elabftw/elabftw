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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\Check;
use Override;
use PDO;

/**
 * Handle the teams to ror relationship. Submodel for teams.
 */
final class Teams2Rors extends AbstractRest
{
    public function __construct(private Teams $Teams, private ?string $ror = null)
    {
        $this->Db = Db::getConnection();
        if ($this->ror !== null) {
            Check::ror($this->ror);
        }
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/teams/%d/rors/', $this->Teams->id);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->Teams->canWriteOrExplode();
        return match ($action) {
            Action::Create => $this->create(),
            default => throw new ImproperActionException('Incorrect action for ROR.'),
        };
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT * FROM teams2rors WHERE teams_id = :teams_id ORDER BY created_at ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':teams_id', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM teams2rors WHERE teams_id = :teams_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':teams_id', $this->Teams->id, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM teams2rors WHERE teams_id = :teams_id AND ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':teams_id', $this->Teams->id, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        return $this->Db->execute($req);
    }

    private function create(): int
    {
        if ($this->ror === null) {
            throw new ImproperActionException('Missing ROR value in URL');
        }
        $sql = 'INSERT IGNORE INTO teams2rors (`teams_id`, `ror`) VALUES (:teams_id, :ror);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':teams_id', $this->Teams->id, PDO::PARAM_INT);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);

        return 1;
    }
}
