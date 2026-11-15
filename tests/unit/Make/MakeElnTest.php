<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Factories\CompoundsFactory;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ZipStream\ZipStream;
use ReflectionMethod;

use function array_column;
use function json_encode;

class MakeElnTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeEln $Make;

    private ZipStream&MockObject $Zip;

    protected function setUp(): void
    {
        $targets = array(
            $this->getFreshExperiment(),
            $this->getFreshExperiment(),
            $this->getFreshItem(),
        );
        $Users = new Users(1, 1);
        $this->Zip = $this->createMock(ZipStream::class);
        $this->Make = new MakeEln(
            $this->createMock(LoggerInterface::class),
            $this->Zip,
            $Users,
            $targets,
            new Instance2Rors(),
            new Teams2Rors($Users->getTeam(), false),
            new Users2Rors($Users->getUserid(), false),
            CompoundsFactory::getCompounds($Users),
        );
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('-export.eln', $this->Make->getFileName());
    }

    public function testGetElnExp(): void
    {
        $this->Zip->expects($this->once())->method('finish');
        $this->Make->getStreamZip();
    }

    public function testMetadataToJsonLd(): void
    {
        $metadata = json_encode(array('extra_fields' => array(
            'Text field' => array('type' => 'text', 'value' => 'The value', 'description' => 'Some description'),
            'Number field' => array('type' => 'number', 'value' => '12', 'unit' => 'mL'),
            'Empty field' => array('type' => 'text'),
            'Multi field' => array(
                'type' => 'text',
                'value' => array('First value', 'Second value'),
                'value_labels' => array('First label', ''),
                'allow_multi_values' => true,
            ),
        )), JSON_THROW_ON_ERROR);
        $res = new ReflectionMethod($this->Make, 'metadataToJsonLd')->invoke($this->Make, $metadata);

        // the full metadata, then one node per single-value field and one node per value of the multi-value field
        $this->assertCount(6, $res['nodes']);
        $this->assertSame(array_column($res['nodes'], '@id'), array_column($res['ids'], '@id'));
        $nodes = array_column($res['nodes'], null, 'propertyID');
        $this->assertSame($metadata, $nodes['elabftw_metadata']['value']);

        foreach ($res['nodes'] as $node) {
            $this->assertSame('PropertyValue', $node['@type']);
            $this->assertArrayNotHasKey('valueReference', $node);
            $this->assertIsNotArray($node['value']);
        }

        $this->assertSame('The value', $nodes['Text field']['value']);
        $this->assertSame('text', $nodes['Text field']['additionalType']);
        $this->assertSame('Some description', $nodes['Text field']['description']);
        $this->assertSame('mL', $nodes['Number field']['unitText']);
        $this->assertSame('', $nodes['Empty field']['value']);

        $this->assertArrayNotHasKey('Multi field', $nodes);
        $this->assertSame('First value', $nodes['Multi field.0']['value']);
        $this->assertSame('Multi field', $nodes['Multi field.0']['name']);
        $this->assertSame('First label', $nodes['Multi field.0']['alternateName']);
        $this->assertSame('Second value', $nodes['Multi field.1']['value']);
        $this->assertArrayNotHasKey('alternateName', $nodes['Multi field.1']);
    }

    public function testMetadataToJsonLdWithReferences(): void
    {
        $metadata = json_encode(array('extra_fields' => array(
            'Experiment' => array('type' => 'experiments', 'value' => 3),
            'Experiments' => array('type' => 'experiments', 'value' => array(4, 3), 'value_labels' => array('Four', 'Three'), 'allow_multi_values' => true),
            'Not exported' => array('type' => 'items', 'value' => 3),
        )), JSON_THROW_ON_ERROR);
        $references = array(
            'experiments' => array(3 => array('@id' => './experiment-3/')),
        );
        $res = new ReflectionMethod($this->Make, 'metadataToJsonLd')->invoke($this->Make, $metadata, $references);
        $nodes = array_column($res['nodes'], null, 'propertyID');

        $this->assertSame(array('@id' => './experiment-3/'), $nodes['Experiment']['value']);
        $this->assertSame('experiments', $nodes['Experiment']['additionalType']);
        // a value that could not be exported keeps its database id
        $this->assertSame(4, $nodes['Experiments.0']['value']);
        $this->assertSame('Four', $nodes['Experiments.0']['alternateName']);
        $this->assertSame(array('@id' => './experiment-3/'), $nodes['Experiments.1']['value']);
        $this->assertSame('Experiments', $nodes['Experiments.1']['name']);
        // only references of the type of the field are used
        $this->assertSame(3, $nodes['Not exported']['value']);
    }
}
