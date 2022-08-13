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
use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use function in_array;
use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;

class EntityParams extends ContentParams implements EntityParamsInterface
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'title' => Filter::title($this->content),
            // MySQL with throw an error if this param is incorrect
            'date', 'metadata' => $this->getUnfilteredContent(),
            'body', 'bodyappend' => $this->getBody(),
            'canread', 'canwrite' => Check::Visibility($this->content),
            'color' => Check::color($this->content),
            'category', 'bookable', 'content_type', 'rating', 'userid', 'state' => $this->getInt(),
            // TODO 'metadatafield' => $this->updateJsonField($params);
            default => throw new ImproperActionException('Invalid update target.'),
        };
    }

    public function getColumn(): string
    {
        if ($this->target === 'bodyappend') {
            return 'body';
        }
        return parent::getColumn();
    }

    public function getField(): string
    {
        return json_encode($this->content ?? '', JSON_HEX_APOS | JSON_THROW_ON_ERROR);
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
