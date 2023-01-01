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
        $this->assertFalse($metadata->hasElabftwNamespace);
        $this->assertFalse($metadata->hasExtraFields);
        $this->assertFalse($metadata->extraFieldsInElabftwNamespace);
        $this->assertTrue($metadata->displayMainText);
        $this->assertNull($metadata->getExtraFieldsJsonPath());
    }

    public function testDisplayMainText(): void
    {
        $metadata = new Metadata('{"elabftw":{"display_main_text":false}}');
        $this->assertTrue($metadata->hasElabftwNamespace);
        $this->assertFalse($metadata->extraFieldsInElabftwNamespace);
        $this->assertFalse($metadata->displayMainText);
        $this->assertNull($metadata->getExtraFieldsJsonPath());
    }

    public function testExtraFieldsElabftwNamespace(): void
    {
        $metadata = new Metadata('{"elabftw":{"extra_fields":{"foo":{"type":"text","value":"bar"}}}}');
        $this->assertTrue($metadata->hasElabftwNamespace);
        $this->assertTrue($metadata->extraFieldsInElabftwNamespace);
        $this->assertTrue($metadata->hasExtraFields);
        $this->assertEquals('$.elabftw.extra_fields', $metadata->getExtraFieldsJsonPath());
    }

    public function testExtraFieldsRoot(): void
    {
        $metadata = new Metadata('{"extra_fields":{"foo":{"type":"text","value":"bar"}}}');
        $this->assertFalse($metadata->hasElabftwNamespace);
        $this->assertFalse($metadata->extraFieldsInElabftwNamespace);
        $this->assertTrue($metadata->hasExtraFields);
        $this->assertEquals('$.extra_fields', $metadata->getExtraFieldsJsonPath());
    }

    public function testGetFormated(): void
    {
        $metadata = new Metadata('{"extra_fields":{"foo":{"type":"text","value":"bar","description":"buzz"}}}');
        $expected = '<h4>foo</h4><h5>buzz</h5><p>bar</p>';
        $this->assertEquals($expected, $metadata->getFormated());
    }

    public function testBlankValueOnDuplicate(): void
    {
        $json = '{"extra_fields":{"To blank":{"type":"text","value":"some value","position":1,"blank_value_on_duplicate":true}}}';
        $blankedJson = '{"extra_fields":{"To blank":{"type":"text","value":"","position":1,"blank_value_on_duplicate":true}}}';
        $this->assertEquals($blankedJson, (new Metadata($json))->blankExtraFieldsValueOnDuplicate());

        $json = '{"extra_fields":{"To blank":{"type":"text","value":"some value","position":1}}}';
        $blankedJson = '{"extra_fields":{"To blank":{"type":"text","value":"some value","position":1}}}';
        $this->assertEquals($blankedJson, (new Metadata($json))->blankExtraFieldsValueOnDuplicate());
    }
}
