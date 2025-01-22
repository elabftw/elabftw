<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

final class StatusParams extends ContentParams
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'color' => Check::color($this->asString()),
            'is_default' => $this->getBinary(),
            'title' => parent::getContent(),
            'state' => $this->getState(),
            default => throw new ImproperActionException('Incorrect parameter for status.'),
        };
    }
}
