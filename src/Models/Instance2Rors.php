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

/**
 * Handle the instance to ror relationship. Submodel for instance.
 */
final class Instance2Rors extends Abstract2Rors
{
    public function __construct(
        bool $canwrite = false,
        ?string $ror = null,
    ) {
        parent::__construct($canwrite, $ror);
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/rors/';
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

    #[Override]
    protected function create(): int
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
