<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

use function sprintf;
use function trim;

/**
 * The GHS hazard and precautionary statements of a compound: submodel of compounds.
 * The sub id is the id of the statement in the reference list.
 */
final class CompoundsGhsStatements extends AbstractRest
{
    public function __construct(private readonly Compounds $Compounds, private readonly ?int $statementId = null)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/ghs_statements/', $this->Compounds->getApiPath(), $this->getCompoundId());
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // checks the read access
        return $this->Compounds->readOne()['ghs_statements'];
    }

    #[Override]
    public function readOne(): array
    {
        foreach ($this->readAll() as $statement) {
            if ((int) $statement['id'] === $this->statementId) {
                return $statement;
            }
        }
        throw new ResourceNotFoundException();
    }

    /**
     * Add a statement from its code, like {"code": "H300"}
     */
    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if ($action !== Action::Create) {
            throw new ImproperActionException('Invalid action for GHS statements.');
        }
        $this->Compounds->canWriteCompoundOrExplode();
        $GhsStatements = new GhsStatements();
        $statement = $GhsStatements->readOneFromCode(trim((string) ($reqBody['code'] ?? '')));
        $GhsStatements->linkToCompound($this->getCompoundId(), array($statement['code']));
        return (int) $statement['id'];
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        if ($this->statementId === null) {
            throw new ImproperActionException('Missing id of the GHS statement to remove.');
        }
        $this->Compounds->canWriteCompoundOrExplode();
        $req = $this->Db->prepare('DELETE FROM compounds2ghs_statements WHERE compound_id = :compound_id AND statement_id = :statement_id');
        $req->bindValue(':compound_id', $this->getCompoundId(), PDO::PARAM_INT);
        $req->bindValue(':statement_id', $this->statementId, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function getCompoundId(): int
    {
        return $this->Compounds->id ?? throw new ImproperActionException('Missing compound id.');
    }
}
