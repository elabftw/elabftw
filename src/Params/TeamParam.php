<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Enums\Units;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Override;

use function implode;
use function in_array;
use function mb_strlen;
use function preg_split;
use function strip_tags;
use function trim;

final class TeamParam extends ContentParams
{
    /** @var int maximum length of a custom unit, matching the qty_unit VARCHAR(10) column */
    private const int MAX_UNIT_LENGTH = 10;

    /** @var int maximum length of the joined custom units, matching the custom_units VARCHAR(255) column */
    private const int MAX_CUSTOM_UNITS_LENGTH = 255;

    #[Override]
    public function getContent(): mixed
    {
        return match ($this->target) {
            'name', 'orgid' => parent::getContent(),
            'custom_units' => $this->getCustomUnits(),
            'hidden_units' => $this->getHiddenUnits(),
            'announcement', 'newcomer_banner',
            'onboarding_email_subject',
            'onboarding_email_body' => $this->getNullableContent(),
            'user_create_tag',
            'force_exp_tpl',
            'force_res_tpl',
            'users_canwrite_experiments',
            'users_canwrite_experiments_categories',
            'users_canwrite_experiments_status',
            'users_canwrite_experiments_templates',
            'users_canwrite_resources',
            'users_canwrite_resources_categories',
            'users_canwrite_resources_status',
            'users_canwrite_resources_templates',
            'visible',
            'newcomer_banner_active',
            'onboarding_email_active' => $this->getBinary(),
            'newcomer_threshold' => $this->asInt(),
            default => throw new ImproperActionException('Incorrect parameter for team.' . $this->target),
        };
    }

    private function getNullableContent(): ?string
    {
        if (empty($this->content)) {
            return null;
        }
        return Filter::body(parent::getContent());
    }

    /**
     * Normalize an admin-entered list of custom container units.
     * Split on comma or newline, trim, strip tags, drop empties, dedupe, drop tokens longer
     * than the qty_unit column allows, and stop before the joined result would overflow the
     * custom_units column. Re-joined with ', '.
     */
    private function getCustomUnits(): ?string
    {
        $tokens = preg_split('/[,\r\n]+/', $this->asString()) ?: array();
        $units = array();
        $length = 0;
        foreach ($tokens as $token) {
            $unit = trim(strip_tags($token));
            if ($unit === '' || mb_strlen($unit) > self::MAX_UNIT_LENGTH || in_array($unit, $units, true)) {
                continue;
            }
            // account for the ', ' separator between units
            $additional = mb_strlen($unit) + ($units === array() ? 0 : 2);
            if ($length + $additional > self::MAX_CUSTOM_UNITS_LENGTH) {
                break;
            }
            $units[] = $unit;
            $length += $additional;
        }
        if ($units === array()) {
            return null;
        }
        return implode(', ', $units);
    }

    /**
     * Normalize the admin-selected list of built-in units to hide from the container dropdowns.
     * Only genuine built-in unit values are kept (hiding a custom unit is meaningless: it is
     * removed via custom_units instead), deduped and joined with ',' (no space) so the value
     * round-trips cleanly through the checkbox UI and the data attributes.
     */
    private function getHiddenUnits(): ?string
    {
        $tokens = preg_split('/[,\r\n]+/', $this->asString()) ?: array();
        $units = array();
        foreach ($tokens as $token) {
            $unit = trim($token);
            if (Units::tryFrom($unit) === null || in_array($unit, $units, true)) {
                continue;
            }
            $units[] = $unit;
        }
        if ($units === array()) {
            return null;
        }
        return implode(',', $units);
    }
}
