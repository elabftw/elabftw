<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\Metadata as MetadataEnum;

use function json_decode;
use function json_encode;

class Metadata
{
    private const int JSON_MAX_DEPTH = 42;

    private array $metadata = array();

    public function __construct(?string $json)
    {
        if ($json === null || $json === 'null') {
            return;
        }
        $decoded = json_decode($json, true, self::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
            $this->metadata = $decoded;
        }
    }

    public function getRaw(): string
    {
        return Tools::printArr($this->metadata);
    }

    // get anything that is not with an extra_fields or elabftw key
    public function getAnyContent(): string
    {
        // copy the array, as we will edit in place
        $res = $this->metadata;
        unset($res[MetadataEnum::ExtraFields->value]);
        unset($res[MetadataEnum::Elabftw->value]);
        return Tools::printArr($res);
    }

    public function getExtraFields(): array
    {
        if (empty($this->metadata) || !isset($this->metadata[MetadataEnum::ExtraFields->value])) {
            return array();
        }
        // sort the elements based on the position attribute. If not set, will be at the end.
        $extraFields = $this->metadata[MetadataEnum::ExtraFields->value];
        uasort($extraFields, function (array $a, array $b): int {
            return ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999);
        });
        return $extraFields;
    }

    public function getDisplayMainText(): bool
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::DisplayMainText->value])) {
            return !$this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::DisplayMainText->value] === false;
        }
        return true;
    }

    public function getGroups(): array
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::Groups->value])) {
            $groups = $this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::Groups->value];
            return array_combine(array_column($groups, 'id'), $groups);
        }
        return array(-1 => array('id' => -1, 'name' => _('Undefined group')));
    }

    public function getGroupedExtraFields(): array
    {
        $groups = $this->getGroups();
        $extraFields = $this->getExtraFields();
        // loop over the extra fields and assign their properties to a group's extra_fields array
        // the name being the key, we merge it into the properties with a "name" key
        foreach ($extraFields as $key => $properties) {
            // default group id for extra fields with invalid or no group_id
            $groupId = -1;
            // if the group_id of the extra field is not defined in groups, it will endup in the default group, with the ones that don't have group_id property
            if (isset($properties[MetadataEnum::GroupId->value]) && in_array((int) $properties[MetadataEnum::GroupId->value], array_column($groups, 'id'), true)) {
                $groupId = (int) $properties[MetadataEnum::GroupId->value];
            } else {
                // add it to the default group
                // if the default group doesn't exist, create it
                // if all the extra fields are assigned to an existing group, there won't be this default group
                if (!isset($groups[-1])) {
                    $groups[-1] = array('id' => -1, 'name' => _('Undefined group'));
                }
            }
            $groups[$groupId]['extra_fields'][] = array_merge($properties, array('name' => $key));
        }
        return $groups;
    }

    /**
     * Get json encoded string of metadata with blanked values for
     * extra fields with 'blank_value_on_duplicate' is true
     */
    public function blankExtraFieldsValueOnDuplicate(): ?string
    {
        $extraFields = $this->getExtraFields();
        if (empty($extraFields)) {
            return null;
        }

        foreach ($extraFields as &$field) {
            if (isset($field[MetadataEnum::BlankValueOnDuplicate->value])
                && $field[MetadataEnum::BlankValueOnDuplicate->value] === true
            ) {
                $field[MetadataEnum::Value->value] = '';
            }
        }
        $this->metadata[MetadataEnum::ExtraFields->value] = $extraFields;

        return json_encode($this->metadata, JSON_THROW_ON_ERROR);
    }
}
