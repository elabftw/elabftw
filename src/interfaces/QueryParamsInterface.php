<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Symfony\Component\HttpFoundation\InputBag;

interface QueryParamsInterface
{
    public function getSql(): string;

    public function getQuery(): InputBag;

    public function setSort(Sort $sort): self;

    public function setStates(array $states): self;

    public function setOrderby(Orderby $orderby): self;
}
