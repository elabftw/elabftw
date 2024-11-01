<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\Orderby;
use Symfony\Component\HttpFoundation\InputBag;

class CompoundsQueryParams extends BaseQueryParams
{
    public Orderby $orderby = Orderby::Lastchange;

    public function __construct(InputBag $query)
    {
        parent::__construct(query: $query, orderby: $this->orderby);
    }
}
