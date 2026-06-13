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
use Elabftw\Services\Check;
use Override;

/**
 * Handle the instance to ror relationship. Submodel for instance.
 */
final class Instance2Rors extends AbstractRest
{
    public function __construct(
        private readonly bool $canwrite = false,
        private readonly ?string $ror = null,
    ) {
        $this->Db = Db::getConnection();
        if ($this->ror !== null) {
            Check::ror($this->ror);
        }
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/rors/';
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->canwriteOrExplode();
        return match ($action) {
            Action::Create => $this->create(),
            default => throw new ImproperActionException('Incorrect action for ROR.'),
        };
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT * FROM instance2rors ORDER BY created_at ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM instance2rors WHERE ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canwriteOrExplode();
        $sql = 'DELETE FROM instance2rors WHERE ror = :ror';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':ror', $this->ror);
        return $this->Db->execute($req);
    }

    private function canwriteOrExplode(): void
    {
        if (!$this->canwrite) {
            throw new IllegalActionException();
        }
    }

    private function create(): int
    {
        if ($this->ror === null) {
            throw new ImproperActionException('Missing ROR value in URL');
        }
        $sql = 'INSERT IGNORE INTO instance2rors (`ror`) VALUES (:ror);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':ror', $this->ror);
        $this->Db->execute($req);

        return 1;
    }
}
