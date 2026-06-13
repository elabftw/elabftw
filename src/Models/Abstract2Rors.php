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
use Elabftw\Services\Check;
use Override;

/**
 * Mother class for *2Rors
 */
abstract class Abstract2Rors extends AbstractRest
{
    public function __construct(
        protected readonly bool $canwrite = false,
        protected readonly ?string $ror = null,
    ) {
        $this->Db = Db::getConnection();
        if ($this->ror !== null) {
            Check::ror($this->ror);
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

    protected function canwriteOrExplode(): void
    {
        if (!$this->canwrite) {
            throw new IllegalActionException();
        }
    }

    abstract protected function create(): int;
}
