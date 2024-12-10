<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

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
            'state' => ProcurementState::from((int) $this->content)->value,
            'qty_received' => $this->getInt(),
            default => throw new ImproperActionException('Incorrect target parameter.'),
        };
    }
}
