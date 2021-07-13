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

use Elabftw\Interfaces\ItemTypeParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class ItemTypeParams extends EntityParams implements ItemTypeParamsInterface
{
    public function __construct(string $content = '', string $target = '', ?array $extra = null)
    {
        parent::__construct($content, $target, $extra);
    }

    public function getBody(): string
    {
        return Filter::body($this->extra['body']);
    }

    public function getCanread(): string
    {
        return Check::visibility($this->extra['canread']);
    }

    public function getCanwriteS(): string
    {
        return Check::visibility($this->extra['canwrite']);
    }

    public function getColor(): string
    {
        return Check::color($this->extra['color']);
    }

    public function getContent(): string
    {
        return Filter::title($this->content);
    }

    public function getIsBookable(): int
    {
        return (int) ($this->extra['bookable'] ?? 0);
    }
}
