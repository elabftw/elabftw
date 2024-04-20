<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class StatusParams extends ContentParams
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'color' => Check::color($this->content),
            'is_default' => Filter::toBinary($this->content),
            'title' => parent::getContent(),
            'state' => $this->getInt(),
            default => throw new ImproperActionException('Incorrect parameter for status.'),
        };
    }
}
