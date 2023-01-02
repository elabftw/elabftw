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

class MetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testNoMetadata(): void
    {
        $metadata = new Metadata(null);
        $this->assertNull($metadata->getExtraFields());
        $this->assertTrue($metadata->getDisplayMainText());
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
