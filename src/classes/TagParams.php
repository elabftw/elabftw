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

use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Filter;

final class TagParams extends ContentParams implements ContentParamsInterface
{
    public function getContent(): string
    {
        return Filter::tag($this->content);
    }
}
