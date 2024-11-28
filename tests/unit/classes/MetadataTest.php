<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class MetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testNoMetadata(): void
    {
        $metadata = new Metadata(null);
        $this->assertEmpty($metadata->getExtraFields());
        $this->assertTrue($metadata->getDisplayMainText());
    }

    public function testGetRaw(): void
    {
        $metadata = new Metadata('{"answer": 42, "lucky numbers": [ 3, 10, 12, 21, 29, 42 ]}');
        $this->assertIsString($metadata->getRaw());
    }

    public function testWithExtraFields(): void
    {
        $metadata = new Metadata('{"extra_fields":{"foo":{"type":"text","value":"bar"}}}');
        $this->assertIsArray($metadata->getExtraFields());
    }

    public function testGetDisplayMainText(): void
    {
        $metadata = new Metadata('{"elabftw": {"display_main_text": false}}');
        $this->assertFalse($metadata->getDisplayMainText());
    }

    public function testGetGroups(): void
    {
        $metadata = new Metadata('{"elabftw": {"extra_fields_groups": [ { "id": 1, "name": "my group"} ] }}');
        $this->assertEquals(1, count($metadata->getGroups()));
        // now with missing id (#5369)
        $metadata = new Metadata('{"elabftw": {"extra_fields_groups": [ { "iiid": 1, "name": "my group"}, { "name": "group2"}, { "id": 1, "name": "group3"} ] }}');
        $this->assertEquals(1, count($metadata->getGroups()));
    }

    public function testGetGroupedExtraFields(): void
    {
        $metadata = new Metadata('{"elabftw": {"extra_fields_groups": [ { "id": 1, "name": "my group"} ] }, "extra_fields":{"foo":{"group_id": 1,"value":"bar"}, "nogroup": {"value": ""}}}');
        $this->assertEquals(2, count($metadata->getGroupedExtraFields()));
    }

    public function testBlankValueOnDuplicate(): void
    {
        $json = '{"extra_fields":{"To blank":{"type":"text","value":"some value","position":1,"blank_value_on_duplicate":true}}}';
        $blankedJson = '{"extra_fields":{"To blank":{"type":"text","value":"","position":1,"blank_value_on_duplicate":true}}}';
        $this->assertEquals($blankedJson, (new Metadata($json))->blankExtraFieldsValueOnDuplicate());

        $json = '{"extra_fields":{"To blank":{"type":"text","value":"some value","position":1}}}';
        $this->assertEquals($json, (new Metadata($json))->blankExtraFieldsValueOnDuplicate());

        $this->assertNull((new Metadata(null))->blankExtraFieldsValueOnDuplicate());
    }
}
