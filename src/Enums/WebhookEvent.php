<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function array_map;
use function implode;
use function in_array;
use function is_string;

/**
 * The events that a webhook can subscribe to.
 * Deliberately small for a first version: only entity changes, and only for
 * concrete entities. Templates and items types do not emit anything.
 */
enum WebhookEvent: string
{
    case ExperimentCreated = 'experiment.created';
    case ExperimentUpdated = 'experiment.updated';
    case ExperimentStatusChanged = 'experiment.status_changed';
    case ItemCreated = 'item.created';
    case ItemUpdated = 'item.updated';
    case ItemStatusChanged = 'item.status_changed';

    /**
     * Map a changelog write to an event, or null if that write is not observable.
     * The changelog is where "something changed" is already recorded, so this is
     * the only place that needs to know about targets.
     */
    public static function fromChangelogTarget(EntityType $entityType, string $target): ?self
    {
        return match ($entityType) {
            EntityType::Experiments => match ($target) {
                'created' => self::ExperimentCreated,
                'status' => self::ExperimentStatusChanged,
                default => self::ExperimentUpdated,
            },
            EntityType::Items => match ($target) {
                'created' => self::ItemCreated,
                'status' => self::ItemStatusChanged,
                default => self::ItemUpdated,
            },
            // no events for templates and items types in this version
            EntityType::Templates, EntityType::ItemsTypes => null,
        };
    }

    public static function toCsList(): string
    {
        return implode(', ', array_map(fn(self $case): string => $case->value, self::cases()));
    }

    /**
     * Keep only valid event names from user input, discarding anything unknown.
     * @return array<int, string>
     */
    public static function filterValid(array $events): array
    {
        $valid = array();
        foreach ($events as $event) {
            if (!is_string($event)) {
                continue;
            }
            $case = self::tryFrom($event);
            if ($case !== null && !in_array($case->value, $valid, true)) {
                $valid[] = $case->value;
            }
        }
        return $valid;
    }
}
