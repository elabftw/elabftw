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
use function sprintf;

class Metadata
{
    // do we display the main body of a concrete entity? {"elabftw": {"display_main_text": false}}
    public bool $displayMainText = true;

    public bool $extraFieldsInElabftwNamespace = false;

    public bool $hasExtraFields = false;

    protected array $metadata = array();

    protected array $extraFields = array();

    public function __construct(protected ?string $json)
    {
        if ($json === null) {
            return;
        }
        $this->metadata = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->check();
    }

    /**
     * Format metadata into a displayable array
     */
    public function getFormated(): string
    {
        $final = '';
        foreach ($this->extraFields as $key => $properties) {
            $description = isset($properties[MetadataEnum::Description->value])
                ? sprintf('<h5>%s</h5>', $properties[MetadataEnum::Description->value])
                : '';
            $final .= sprintf('<h4>%s</h4>%s<p>%s</p>', $key, $description, $properties[MetadataEnum::Value->value]);
        }
        return $final;
    }

    /**
     * Get json encoded string of metadata with blanked values for
     * extra fields with 'blank_value_on_duplicate' is true
     */
    public function blankExtraFieldsValueOnDuplicate(): ?string
    {
        if (empty($this->metadata) && empty($this->extraFields)) {
            return null;
        }

        foreach ($this->extraFields as &$field) {
            if (isset($field[MetadataEnum::BlankValueOnDuplicate->value])
                && $field[MetadataEnum::BlankValueOnDuplicate->value] === true
            ) {
                $field[MetadataEnum::Value->value] = '';
            }
        }

        return json_encode($this->metadata, JSON_THROW_ON_ERROR);
    }

    /**
     * jsonPath to extra_fields, with/out elabftw namespace
     */
    public function getExtraFieldsJsonPath(): ?string
    {
        if (!$this->hasExtraFields) {
            return null;
        }

        return sprintf(
            '$.%s%s',
            $this->extraFieldsInElabftwNamespace
                ? MetadataEnum::Elabftw->value . '.'
                : '',
            MetadataEnum::ExtraFields->value,
        );
    }

    protected function check(): void
    {
        $this->checkElabftwNamespace();

        // here we know if there are extra fields in elabftw namespace
        // extra fields in elabftw namespace have precedence over extra fields at root
        if ($this->hasExtraFields === false) {
            $this->checkExtraFieldsRoot();
        }
    }

    protected function checkElabftwNamespace(): void
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value])) {
            $this->checkDisplayMainText();
            $this->checkExtraFieldsElabftw();
        }
    }

    protected function checkDisplayMainText(): void
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::DisplayMainText->value])
            && $this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::DisplayMainText->value] === false
        ) {
            $this->displayMainText = false;
        }
    }

    /**
     * Are there any extra fields in elabftw namespace
     * extra fields in elabftw namespace have precedence over extra fields at root
     */
    protected function checkExtraFieldsElabftw(): void
    {
        if (isset($this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::ExtraFields->value])) {
            $this->extraFieldsInElabftwNamespace = true;
            $this->hasExtraFields = true;
            $this->extraFields = &$this->metadata[MetadataEnum::Elabftw->value][MetadataEnum::ExtraFields->value];
        }
    }

    /**
     * Are there any extra fields in root
     * extra fields in elabftw namespace have precedence over extra fields at root
     */
    protected function checkExtraFieldsRoot(): void
    {
        if (isset($this->metadata[MetadataEnum::ExtraFields->value])) {
            $this->hasExtraFields = true;
            $this->extraFields = &$this->metadata[MetadataEnum::ExtraFields->value];
        }
    }
}
