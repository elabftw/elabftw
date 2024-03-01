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
            'canread', 'canwrite', 'canbook', 'canread_target', 'canwrite_target' => Check::Visibility($this->content),
            'color' => Check::color($this->content),
            'is_bookable', 'book_can_overlap', 'book_users_can_in_past', 'book_max_minutes', 'book_max_slots', 'book_is_cancellable', 'book_cancel_minutes', 'content_type', 'rating', 'userid', 'state' => $this->getInt(),
            'custom_id', 'status', 'category' => $this->getIntOrNull(),
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
}
