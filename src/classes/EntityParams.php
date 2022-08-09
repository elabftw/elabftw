<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use function in_array;
use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;

class EntityParams extends ContentParams implements EntityParamsInterface
{
    public function getColumn(): string
    {
        if ($this->target === 'bodyappend') {
            return 'body';
        }
        return parent::getColumn();
    }

    public function getTitle(): string
    {
        return Filter::title($this->content);
    }

    public function getTags(): array
    {
        return $this->extra['tags'] ?? array();
    }

    public function getExtraBody(): string
    {
        return Filter::body($this->extra['body'] ?? '');
    }

    public function getColor(): string
    {
        return Check::color($this->content);
    }

    public function getField(): string
    {
        return json_encode($this->extra['jsonField'] ?? '', JSON_HEX_APOS | JSON_THROW_ON_ERROR);
    }

    public function getVisibility(): string
    {
        return Check::Visibility($this->content);
    }

    public function getState(): int
    {
        $state = (int) $this->content;
        // TODO in php 8.1, we will use an enum for this
        if (!in_array($state, array(1, 2, 3), true)) {
            return 1;
        }
        return $state;
    }
}
