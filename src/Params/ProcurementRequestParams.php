<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Enums\ProcurementState;
use Elabftw\Exceptions\ImproperActionException;
use Override;

final class ProcurementRequestParams extends ContentParams
{
    #[Override]
    public function getContent(): mixed
    {
        return match ($this->target) {
            // TODO getComment()?
            'body' => $this->getBody(),
            'state' => $this->getEnum(ProcurementState::class, (int) $this->content)->value,
            'qty_received' => $this->asInt(),
            default => throw new ImproperActionException('Incorrect target parameter.'),
        };
    }
}
