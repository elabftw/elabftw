<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

final class TeamGroupParams extends ContentParams
{
    // TODO remove this as it's same as parent?
    public function getContent(): string|int
    {
        return match ($this->target) {
            'name' => parent::getContent(),
            default => throw new ImproperActionException('Incorrect parameter for teamgroup.'),
        };
    }
}
