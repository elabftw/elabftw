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
use Override;

final class EntityParams extends ContentParams implements ContentParamsInterface
{
    #[Override]
    public function getContent(): mixed
    {
        return match ($this->target) {
            'title' => Filter::title($this->asString()),
            // MySQL with throw an error if this param is incorrect
            'date', 'metadata',
            'is_procurable', 'proc_price_notax', 'proc_price_tax' => $this->getUnfilteredContent(), 'proc_currency' => Currency::from($this->asInt())->value,
            'body', 'bodyappend' => $this->getBody(),
            'canread', 'canwrite', 'canbook', 'canread_target', 'canwrite_target' => $this->getCanJson(),
            'canread_base', 'canwrite_base', 'canbook_base', 'canread_target_base', 'canwrite_target_base', 'canbook_target_base' => $this->getCanBase(),
            'color' => Check::color($this->asString()),
            'is_bookable', 'book_max_minutes', 'book_max_slots', 'book_is_cancellable', 'book_cancel_minutes', 'book_can_overlap', 'book_users_can_in_past',
            'book_price_notax', 'book_price_tax' => $this->getUnfilteredContent(), 'book_currency' => Currency::from($this->asInt())->value,
            'content_type', 'proc_pack_qty', 'rating', 'userid', 'team' => $this->asInt(),
            'state' => $this->getState(),
            'custom_id', 'status', 'category', 'storage', 'qty_stored' => $this->getPositiveIntOrNull(),
            'canread_is_immutable', 'canwrite_is_immutable', 'hide_main_text' => $this->getBinary(),
            'qty_unit' => Check::unit($this->asString()),
            default => throw new ImproperActionException('Invalid update target.'),
        };
    }

    #[Override]
    public function getColumn(): string
    {
        if ($this->target === 'bodyappend') {
            return 'body';
        }
        return parent::getColumn();
    }
}
