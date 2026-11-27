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

use Elabftw\Enums\BasePermissions;
use Elabftw\Factories\CompoundsFactory;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ZipStream\ZipStream;
use ReflectionMethod;

use function array_column;
use function array_filter;
use function array_keys;
use function array_values;
use function json_decode;
use function json_encode;
use function str_ends_with;

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

    public function testPermissionsToJsonLd(): void
    {
        $User = $this->getRegularUserInTeam(1);
        $e = array(
            'team' => 1,
            'canread_base' => BasePermissions::User->value,
            'canwrite_base' => BasePermissions::UserOnly->value,
            'canread' => json_encode(array('teams' => array(1), 'teamgroups' => array(), 'users' => array($User->getUserid())), JSON_THROW_ON_ERROR),
            'canwrite' => AbstractEntity::EMPTY_CAN_JSON,
            'canread_is_immutable' => 1,
            'canwrite_is_immutable' => 0,
        );
        $res = new ReflectionMethod($this->Make, 'permissionsToJsonLd')->invoke($this->Make, $e);

        // an experiment has no target or booking permissions
        $this->assertSame(array('canread', 'canwrite'), array_keys($res));
        $this->assertSame(BasePermissions::User->value, $res['canread']['base']);
        $this->assertSame(BasePermissions::UserOnly->value, $res['canwrite']['base']);
        $this->assertTrue($res['canread']['is_immutable']);
        $this->assertFalse($res['canwrite']['is_immutable']);
        // database ids are not exported: teams by name, users as Person nodes
        $this->assertCount(1, $res['canread']['teams']);
        $this->assertNotSame('', $res['canread']['teams'][0]['name']);
        $this->assertTrue($res['canread']['teams'][0]['is_entity_team']);
        $this->assertCount(1, $res['canread']['users']);
        $this->assertStringStartsWith('person://', $res['canread']['users'][0]['@id']);
        $this->assertSame(array(), $res['canwrite']['users']);
    }

    public function testPermissionsToJsonLdTemplateAndBookable(): void
    {
        $e = array(
            'team' => 1,
            'canread_base' => BasePermissions::Team->value,
            'canwrite_base' => BasePermissions::User->value,
            'canread' => AbstractEntity::EMPTY_CAN_JSON,
            'canwrite' => AbstractEntity::EMPTY_CAN_JSON,
            'canread_is_immutable' => 0,
            'canwrite_is_immutable' => 1,
            'canread_target_base' => BasePermissions::UserOnly->value,
            'canwrite_target_base' => BasePermissions::UserOnly->value,
            'canread_target' => AbstractEntity::EMPTY_CAN_JSON,
            'canwrite_target' => AbstractEntity::EMPTY_CAN_JSON,
            'canbook_base' => BasePermissions::Organization->value,
            'canbook' => AbstractEntity::EMPTY_CAN_JSON,
        );
        $res = new ReflectionMethod($this->Make, 'permissionsToJsonLd')->invoke($this->Make, $e);

        $this->assertSame(array('canread', 'canwrite', 'canread_target', 'canwrite_target', 'canbook'), array_keys($res));
        $this->assertSame(BasePermissions::UserOnly->value, $res['canread_target']['base']);
        $this->assertSame(BasePermissions::Organization->value, $res['canbook']['base']);
        // immutability belongs to the entry itself, not to its targets or booking
        $this->assertArrayNotHasKey('is_immutable', $res['canread_target']);
        $this->assertArrayNotHasKey('is_immutable', $res['canbook']);
        $this->assertTrue($res['canwrite']['is_immutable']);
    }

    public function testExportContainsPermissions(): void
    {
        $Experiment = $this->getFreshExperiment();
        $Experiment->update(new EntityParams('canread_base', BasePermissions::User->value));
        $Experiment->update(new EntityParams('canwrite_base', BasePermissions::UserOnly->value));
        // update() does not refresh entityData, which is what the export reads
        $Experiment->readOne();
        $dataset = $this->exportDataset($Experiment);
        $permissions = $dataset[MakeEln::PERMISSIONS_PROPERTY];
        $this->assertSame('@json', $permissions['@type']);
        $this->assertSame(BasePermissions::User->value, $permissions['@value']['canread']['base']);
        $this->assertSame(BasePermissions::UserOnly->value, $permissions['@value']['canwrite']['base']);
    }

    public function testExportWithoutPermissions(): void
    {
        $dataset = $this->exportDataset($this->getFreshExperiment(), includePermissions: false);
        $this->assertArrayNotHasKey(MakeEln::PERMISSIONS_PROPERTY, $dataset);
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

    /**
     * Export a single entity and return its Dataset node from the generated ro-crate-metadata.json
     */
    private function exportDataset(AbstractEntity $entity, bool $includePermissions = true): array
    {
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $files = array();
        $Zip->method('addFile')->willReturnCallback(function (string $fileName, string $data) use (&$files): void {
            $files[$fileName] = $data;
        });
        $Make = new MakeEln(
            $this->createMock(LoggerInterface::class),
            $Zip,
            $Users,
            array($entity),
            new Instance2Rors(),
            new Teams2Rors($Users->getTeam(), false),
            new Users2Rors($Users->getUserid(), false),
            CompoundsFactory::getCompounds($Users),
            includePermissions: $includePermissions,
        );
        $Make->getStreamZip();

        $metadataFile = array_values(array_filter(array_keys($files), fn(string $name): bool => str_ends_with($name, '/ro-crate-metadata.json')));
        $this->assertCount(1, $metadataFile);
        $graph = json_decode($files[$metadataFile[0]], true, 512, JSON_THROW_ON_ERROR)['@graph'];
        $datasets = array_values(array_filter($graph, fn(array $node): bool => ($node['@type'] ?? '') === 'Dataset' && $node['@id'] !== './'));
        $this->assertCount(1, $datasets);
        return $datasets[0];
    }
}
