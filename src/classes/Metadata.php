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

    public function getExtraFields(): ?array
    {
        if (empty($this->metadata) || !isset($this->metadata[MetadataEnum::ExtraFields->value])) {
            return null;
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

    /**
     * Get json encoded string of metadata with blanked values for
     * extra fields with 'blank_value_on_duplicate' is true
     */
    public function blankExtraFieldsValueOnDuplicate(): ?string
    {
        $extraFields = $this->getExtraFields();
        if ($extraFields === null) {
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
