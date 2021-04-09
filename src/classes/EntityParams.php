<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Filter;

final class EntityParams extends UpdateParams implements EntityParamsInterface
{
    public function getTitle(): string
    {
        return Filter::title($this->content);
    }

    public function getDate(): string
    {
        return Filter::kdate($this->content);
    }

    public function getBody(): string
    {
        return Filter::body($this->content);
    }
}
