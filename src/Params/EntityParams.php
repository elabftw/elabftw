<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Enums\Currency;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

class EntityParams extends ContentParams implements ContentParamsInterface
{
    public function getContent(): mixed
    {
        return match ($this->target) {
            'title' => Filter::title($this->asString()),
            // MySQL with throw an error if this param is incorrect
            'date', 'metadata', 'proc_price_notax', 'proc_price_tax' => $this->getUnfilteredContent(),
            'proc_currency' => Currency::from($this->asInt())->value,
            'body', 'bodyappend' => $this->getBody(),
            'canread', 'canwrite', 'canbook', 'canread_target', 'canwrite_target' => $this->getCanJson(),
            'color' => Check::color($this->asString()),
            'book_max_minutes', 'book_max_slots', 'book_is_cancellable', 'book_cancel_minutes', 'content_type', 'proc_pack_qty', 'rating', 'userid' => $this->asInt(),
            'state' => $this->getState(),
            'custom_id', 'status', 'category', 'storage', 'qty_stored' => $this->getPositiveIntOrNull(),
            'is_procurable', 'book_can_overlap', 'book_users_can_in_past', 'is_bookable', 'canread_is_immutable', 'canwrite_is_immutable' => $this->getBinary(),
            'qty_unit' => Check::unit($this->asString()),
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
