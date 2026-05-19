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

use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\Currency;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Override;

use function filter_var;
use function is_int;
use function is_string;

use const FILTER_VALIDATE_INT;

final class EntityParams extends ContentParams implements ContentParamsInterface
{
    public function __construct(string $target, mixed $content, private readonly ?BodyContentType $bodyContentType = null)
    {
        parent::__construct($target, $content);
    }

    #[Override]
    public function getContent(): mixed
    {
        return match ($this->target) {
            'title' => Filter::title($this->asString()),
            // MySQL with throw an error if this param is incorrect
            'date', 'metadata', 'proc_price_notax', 'proc_price_tax', 'booking_hourly_rate_notax', 'booking_hourly_rate_tax' => $this->getUnfilteredContent(),
            'proc_currency', 'booking_hourly_rate_currency' => Currency::from($this->asInt())->value,
            'body', 'bodyappend' => $this->getBody(),
            'canread', 'canwrite', 'canbook', 'canread_target', 'canwrite_target' => $this->getCanJson(),
            'canread_base', 'canwrite_base', 'canbook_base', 'canread_target_base', 'canwrite_target_base', 'canbook_target_base' => $this->getCanBase(),
            'color' => Check::color($this->asString()),
            'book_max_minutes', 'book_max_slots', 'book_cancel_minutes', 'booking_window_days', 'proc_pack_qty', 'rating', 'userid', 'team' => $this->asInt(),
            'content_type' => $this->getContentType(),
            'state' => $this->getState(),
            'custom_id', 'status', 'category', 'storage', 'qty_stored' => $this->getPositiveIntOrNull(),
            'is_procurable', 'book_can_overlap', 'book_is_cancellable', 'book_users_can_in_past', 'is_bookable', 'canread_is_immutable', 'canwrite_is_immutable', 'hide_main_text' => $this->getBinary(),
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

    public function getBodyContentType(): ?BodyContentType
    {
        return $this->bodyContentType;
    }

    private function getContentType(): int
    {
        $contentType = $this->content;
        if (is_string($contentType)) {
            $contentType = filter_var($contentType, FILTER_VALIDATE_INT);
        }
        if (!is_int($contentType)) {
            throw new ImproperActionException('Invalid content_type parameter.');
        }
        return BodyContentType::tryFrom($contentType)?->value ?? throw new ImproperActionException('Invalid content_type parameter.');
    }
}
