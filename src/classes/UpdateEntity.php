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

use Elabftw\Interfaces\UpdateEntityParamsInterface;
use Elabftw\Services\Filter;

final class UpdateEntity extends UpdateParams implements UpdateEntityParamsInterface
{
    // target can be title, date or body
    public function __construct(string $target, string $content)
    {
        parent::__construct($content);
        $this->target = $target;
    }

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
