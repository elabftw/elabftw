<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

class EntityParams extends ContentParams implements ContentParamsInterface
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

    public function getState(): int
    {
        $state = State::tryFrom((int) $this->content) ?? State::Normal;
        return $state->value;
    }
}
