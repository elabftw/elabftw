<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Metadata as MetadataEnum;
use function json_decode;
use function json_encode;

class Metadata
{
    private const JSON_MAX_DEPTH = 42;

    private array $metadata = array();

    public function __construct(?string $json)
    {
        if ($json === null) {
            return;
        }
        $this->metadata = json_decode($json, true, self::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
    }

    public function getExtraFields(): array
    {
        if (empty($this->metadata) || !isset($this->metadata[MetadataEnum::ExtraFields->value])) {
            return array();
        }
        return $this->metadata[MetadataEnum::ExtraFields->value];
    }

    public function getDisplayMainText(): bool
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value])) {
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
            if (isset($properties[MetadataEnum::GroupId->value])) {
                $groups[$properties[MetadataEnum::GroupId->value]]['extra_fields'][] = array_merge($properties, array('name' => $key));
            } else {
                // add it to the default group
                // if the default group doesn't exist, create it
                if (!isset($groups[-1])) {
                    $groups[-1] = array('id' => -1, 'name' => _('Undefined group'));
                }
                $groups[-1]['extra_fields'][] = array_merge($properties, array('name' => $key));
            }
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
