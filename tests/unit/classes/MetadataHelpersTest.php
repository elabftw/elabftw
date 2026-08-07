<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

use function json_decode;
use function json_encode;
use function sprintf;

class MetadataHelpersTest extends \PHPUnit\Framework\TestCase
{
    public function testMergeMetadataWithEmptySource(): void
    {
        $incoming = '{"extra_fields":{"Website":{"type":"url","value":"https://example.org"}}}';
        $expected = '{"extra_fields":{"Website":{"type":"url","value":"https://example.org"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata(null, $incoming));
    }

    public function testMergeMetadataPreservesSourceSchema(): void
    {
        $source = '{"extra_fields":{"Weight":{"type":"number","unit":"g","units":["g","kg"],"description":"Mass","value":"1"}}}';
        $incoming = '{"extra_fields":{"Weight":{"type":"text","unit":"kg","description":"Ignored","value":"12,5"}}}';
        $expected = '{"extra_fields":{"Weight":{"type":"number","unit":"g","units":["g","kg"],"description":"Mass","value":"12.5"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataAddsNewFieldWithIncomingSchema(): void
    {
        $source = '{"extra_fields":{"Existing":{"type":"text","value":"old"}}}';
        $incoming = '{"extra_fields":{"Website":{"type":"url","description":"Homepage","value":"https://example.org"}}}';
        $expected = '{"extra_fields":{"Existing":{"type":"text","value":"old"},"Website":{"type":"url","description":"Homepage","value":"https://example.org"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataIgnoresExistingFieldWithoutValue(): void
    {
        $source = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"42"}}}';
        $incoming = '{"extra_fields":{"Weight":{"type":"text","unit":"kg"}}}';

        $this->assertJsonStringEqualsJsonString($source, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataNormalizesNumber(): void
    {
        $source = '{"extra_fields":{"Weight":{"type":"number","value":""}}}';
        $incoming = '{"extra_fields":{"Weight":{"value":"12,5"}}}';
        $expected = '{"extra_fields":{"Weight":{"type":"number","value":"12.5"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataRejectsInvalidNumber(): void
    {
        $source = '{"extra_fields":{"Weight":{"type":"number","value":""}}}';
        $incoming = '{"extra_fields":{"Weight":{"value":"100X89"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Weight expects a number.');
        MetadataHelpers::mergeMetadata($source, $incoming);
    }

    public function testMergeMetadataNormalizesCheckboxTruthyValues(): void
    {
        $source = '{"extra_fields":{"Certified":{"type":"checkbox","value":""}}}';
        $truthyValues = array(
            '1',
            'TRUE',
            'yes',
            'YEP',
            'oui',
            'ouaip',
            'grave',
            'sí',
            'sim',
            'jawohl',
            'sì',
            'da',
            'tak',
            'kyllä',
            'igen',
            'evet',
            'hai',
            'はい',
            '+',
            '✓',
            '✔',
        );

        foreach ($truthyValues as $value) {
            $incoming = json_encode(array(
                'extra_fields' => array(
                    'Certified' => array('value' => $value),
                ),
            ), JSON_THROW_ON_ERROR);
            $result = json_decode(MetadataHelpers::mergeMetadata($source, $incoming), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('on', $result['extra_fields']['Certified']['value'], sprintf('Expected "%s" to normalize to "on".', $value));
        }
    }

    public function testMergeMetadataNormalizesCheckboxFalsyValues(): void
    {
        $source = '{"extra_fields":{"Certified":{"type":"checkbox","value":"on"}}}';
        $falsyValues = array('0', 'false', 'no', 'off', 'non', 'いいえ', 'whatever');

        foreach ($falsyValues as $value) {
            $incoming = json_encode(array(
                'extra_fields' => array(
                    'Certified' => array('value' => $value),
                ),
            ), JSON_THROW_ON_ERROR);
            $result = json_decode(MetadataHelpers::mergeMetadata($source, $incoming), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('', $result['extra_fields']['Certified']['value'], sprintf('Expected "%s" to normalize to an unchecked checkbox.', $value));
        }
    }

    public function testMergeMetadataAcceptsValidSelectOption(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"B"}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"B"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataRejectsInvalidSelectOption(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"C"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Choice contains an invalid option.');
        MetadataHelpers::mergeMetadata($source, $incoming);
    }

    public function testMergeMetadataNormalizesMultiSelect(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","allow_multi_values":true,"options":["A","B","C"],"value":[]}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"A, C"}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select","allow_multi_values":true,"options":["A","B","C"],"value":["A","C"]}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataRejectsMultipleValuesForSingleSelect(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":["A","B"]}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Choice expects a single value.');
        MetadataHelpers::mergeMetadata($source, $incoming);
    }

    public function testMergeMetadataRejectsInvalidExtraFields(): void
    {
        $incoming = '{"extra_fields":"nope"}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata extra_fields provided.');
        MetadataHelpers::mergeMetadata('{}', $incoming);
    }

    public function testMergeMetadataRejectsInvalidField(): void
    {
        $incoming = '{"extra_fields":{"Nope":"not an array"}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata field Nope.');
        MetadataHelpers::mergeMetadata('{}', $incoming);
    }

    public function testMergeMetadataRejectsInvalidJson(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata JSON provided.');
        MetadataHelpers::mergeMetadata('{}', '{nope');
    }

    public function testMergeMetadataValuesWithValueOnlyIncomingField(): void
    {
        $base = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"1"}}}';
        $incoming = '{"extra_fields":{"Weight":{"value":"12,5"}}}';
        $expected = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"12.5"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesWithCompatibleSchema(): void
    {
        $base = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"1"}}}';
        $incoming = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"2"}}}';
        $expected = '{"extra_fields":{"Weight":{"type":"number","unit":"g","value":"2"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesRejectsIncompatibleType(): void
    {
        $base = '{"extra_fields":{"Coffee":{"type":"number","unit":"liter","value":"100"}}}';
        $incoming = '{"extra_fields":{"Coffee":{"type":"date","unit":"liter","value":"100"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Coffee has incompatible type.');
        MetadataHelpers::mergeMetadataValues($base, $incoming);
    }

    public function testMergeMetadataValuesRejectsIncompatibleUnit(): void
    {
        $base = '{"extra_fields":{"Coffee":{"type":"number","unit":"liter","value":"100"}}}';
        $incoming = '{"extra_fields":{"Coffee":{"type":"number","unit":"gram","value":"100"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Coffee has incompatible unit.');
        MetadataHelpers::mergeMetadataValues($base, $incoming);
    }

    public function testMergeMetadataValuesRejectsInvalidNumber(): void
    {
        $base = '{"extra_fields":{"Coffee":{"type":"number","unit":"liter","value":"100"}}}';
        $incoming = '{"extra_fields":{"Coffee":{"value":"100X89"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Coffee expects a number.');
        MetadataHelpers::mergeMetadataValues($base, $incoming);
    }

    public function testMergeMetadataValuesRejectsInvalidSelectOption(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"radio","options":["A","B"],"value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"C"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Choice contains an invalid option.');
        MetadataHelpers::mergeMetadataValues($base, $incoming);
    }

    public function testMergeMetadataValuesKeepsExistingSelectOptions(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"B"}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select","options":["A","B"],"value":"B"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesAddsNewField(): void
    {
        $base = '{"extra_fields":{"Existing":{"type":"text","value":"old"}}}';
        $incoming = '{"extra_fields":{"Website":{"type":"url","value":"https://example.org"}}}';
        $expected = '{"extra_fields":{"Existing":{"type":"text","value":"old"},"Website":{"type":"url","value":"https://example.org"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesRejectsInvalidJson(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata JSON provided.');
        MetadataHelpers::mergeMetadataValues('{}', '{nope');
    }

    public function testMergeMetadataRejectsInvalidSourceField(): void
    {
        $source = '{"extra_fields":{"Nope":"not an array"}}';
        $incoming = '{"extra_fields":{"Nope":{"value":"test"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata field Nope.');
        MetadataHelpers::mergeMetadata($source, $incoming);
    }

    public function testMergeMetadataRejectsNonArrayJson(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata JSON provided.');
        MetadataHelpers::mergeMetadata('null', '{}');
    }

    public function testMergeMetadataNormalizesMultiSelectArray(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","allow_multi_values":true,"options":["A","B","C"],"value":[]}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":["A","C"]}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select","allow_multi_values":true,"options":["A","B","C"],"value":["A","C"]}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataRejectsInvalidOptions(): void
    {
        $source = '{"extra_fields":{"Choice":{"type":"select","options":"nope","value":"A"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"A"}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Choice has invalid options.');
        MetadataHelpers::mergeMetadata($source, $incoming);
    }

    public function testMergeMetadataNormalizesEmptyCheckbox(): void
    {
        $source = '{"extra_fields":{"Certified":{"type":"checkbox","value":"on"}}}';
        $incoming = '{"extra_fields":{"Certified":{"value":""}}}';
        $expected = '{"extra_fields":{"Certified":{"type":"checkbox","value":""}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadata($source, $incoming));
    }

    public function testMergeMetadataValuesNormalizesSelectMultiArray(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"select-multi","options":["A","B"],"value":[]}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":["A","B"]}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select-multi","options":["A","B"],"value":["A","B"]}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesNormalizesSelectMultiString(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"select-multi","options":["A","B","C"],"value":[]}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"A, C"}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select-multi","options":["A","B","C"],"value":["A","C"]}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesRejectsInvalidExtraFields(): void
    {
        $base = '{"extra_fields":"nope"}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata extra_fields provided.');
        MetadataHelpers::mergeMetadataValues($base, '{}');
    }

    public function testMergeMetadataValuesRejectsInvalidField(): void
    {
        $incoming = '{"extra_fields":{"Nope":"not an array"}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid metadata field Nope.');
        MetadataHelpers::mergeMetadataValues('{}', $incoming);
    }

    public function testMergeMetadataValuesAcceptsNumericOptions(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"select","options":[1,2],"value":"2"}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":"1"}}}';
        $expected = '{"extra_fields":{"Choice":{"type":"select","options":[1,2],"value":"1"}}}';

        $this->assertJsonStringEqualsJsonString($expected, MetadataHelpers::mergeMetadataValues($base, $incoming));
    }

    public function testMergeMetadataValuesRejectsNonScalarOption(): void
    {
        $base = '{"extra_fields":{"Choice":{"type":"select-multi","options":["A"],"value":[]}}}';
        $incoming = '{"extra_fields":{"Choice":{"value":[["A"]]}}}';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field Choice contains an invalid option.');
        MetadataHelpers::mergeMetadataValues($base, $incoming);
    }
}
