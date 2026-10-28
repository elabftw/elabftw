<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Enums\EntityType;
use Elabftw\Make\MakeEln;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Compounds;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Storage\Cache\ElabCache;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use ZipStream\ZipStream;

use function basename;
use function json_decode;
use function json_encode;
use function random_int;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ElnMetadataReferencesTest extends TestCase
{
    use TestsUtilsTrait;

    private FilesystemOperator $fs;

    private LoggerInterface $logger;

    private Users $requester;

    protected function setUp(): void
    {
        $this->fs = new ElabCache()->getFs();
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->requester = new Users(1, 1);
    }

    public function testExperimentAndResourceReferencesRoundTrip(): void
    {
        $first = $this->createEntity(EntityType::Experiments, 'ELN metadata first');
        $second = $this->createEntity(EntityType::Experiments, 'ELN metadata second');
        $resource = $this->createEntity(EntityType::Items, 'ELN metadata resource');
        $source = $this->createEntity(EntityType::Experiments, 'ELN metadata source');

        $this->setMetadata($source, array(
            'Single experiment' => array(
                'type' => 'experiments',
                'value' => $first->id,
            ),
            'Multiple experiments' => array(
                'type' => 'experiments',
                'value' => array($second->id, $first->id),
                'allow_multi_values' => true,
            ),
            'Resource' => array(
                'type' => 'items',
                'value' => $resource->id,
            ),
            'Text' => array(
                'type' => 'text',
                'value' => 'unchanged',
            ),
        ));

        [$path, , $crate] = $this->export(array($source));

        try {
            $single = $this->fieldNode($crate, 'Single experiment');
            $multi = $this->fieldNode($crate, 'Multiple experiments');

            $this->assertIsString($single['value']['@id']);
            $this->assertCount(2, $multi['value']);
            $this->assertIsString($multi['value'][0]['@id']);
            $this->assertIsString(
                $this->fieldNode($crate, 'Resource')['value']['@id'],
            );

            $imported = $this->importedByTitle($this->import($path));
            $this->assertCount(4, $imported);

            $metadata = $this->metadata($imported['ELN metadata source']);

            $this->assertNotSame(
                $first->id,
                $imported['ELN metadata first']->id,
            );

            $this->assertSame(
                $imported['ELN metadata first']->id,
                $metadata['Single experiment']['value'],
            );

            $this->assertSame(array(
                $imported['ELN metadata second']->id,
                $imported['ELN metadata first']->id,
            ), $metadata['Multiple experiments']['value']);

            $this->assertSame(
                $imported['ELN metadata resource']->id,
                $metadata['Resource']['value'],
            );

            $this->assertSame('unchanged', $metadata['Text']['value']);
        } finally {
            unlink($path);
        }
    }

    public function testCompoundReferencedOnlyInMetadataIsCreatedAndReused(): void
    {
        $Compounds = new Compounds(
            new HttpGetter(new Client(), '', false),
            $this->requester,
            new NullFingerprinter(),
        );

        $sourceCid = random_int(100000000, 999999999);
        $importedCid = random_int(1000000000, 1999999999);

        $compoundId = $Compounds->create(
            name: 'ELN metadata compound',
            pubchemCid: $sourceCid,
        );

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata compound source',
        );

        $this->setMetadata($source, array(
            'Single compound' => array(
                'type' => 'compounds',
                'value' => $compoundId,
            ),
            'Multiple compounds' => array(
                'type' => 'compounds',
                'value' => array($compoundId),
                'allow_multi_values' => true,
            ),
        ));

        [$path, $metadataPath, $crate] = $this->export(
            array($source),
            $Compounds,
        );

        try {
            // Simulate a compound absent from the destination instance.
            foreach ($crate['@graph'] as &$node) {
                if (($node['@type'] ?? '') !== 'MolecularEntity') {
                    continue;
                }

                foreach ($node['identifier'] as &$identifier) {
                    if ($identifier['propertyID'] === 'PubChem CID') {
                        $identifier['value'] = $importedCid;
                        $identifier['sameAs'] =
                            'https://pubchem.ncbi.nlm.nih.gov/#query=' . $importedCid;
                    }
                }
                unset($identifier);
            }
            unset($node);

            $this->replaceMetadata($path, $metadataPath, $crate);

            $imported = $this->importedByTitle(
                $this->import($path, $Compounds),
            );

            $metadata = $this->metadata(
                $imported['ELN metadata compound source'],
            );

            $newId = $metadata['Single compound']['value'];

            $this->assertNotSame($compoundId, $newId);
            $this->assertSame(
                array($newId),
                $metadata['Multiple compounds']['value'],
            );

            $Compounds->setId($newId);
            $this->assertSame(
                $importedCid,
                (int) $Compounds->readOne()['pubchem_cid'],
            );
        } finally {
            unlink($path);
        }
    }

    public function testExistingCompoundIsReused(): void
    {
        $Compounds = new Compounds(
            new HttpGetter(new Client(), '', false),
            $this->requester,
            new NullFingerprinter(),
        );

        $compoundId = $Compounds->create(
            name: 'ELN compound already present',
            pubchemCid: random_int(100000000, 999999999),
        );

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata existing compound',
        );

        $this->setMetadata($source, array(
            'Compound' => array(
                'type' => 'compounds',
                'value' => $compoundId,
            ),
        ));

        [$path] = $this->export(array($source), $Compounds);

        try {
            $imported = $this->importedByTitle(
                $this->import($path, $Compounds),
            );

            $field = $this->metadata(
                $imported['ELN metadata existing compound'],
            )['Compound'];

            $this->assertSame($compoundId, $field['value']);
        } finally {
            unlink($path);
        }
    }

    public function testUnresolvedReferenceClearsOldIdsAndLabels(): void
    {
        $target = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata unavailable target',
        );

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata missing source',
        );

        $this->setMetadata($source, array(
            'Missing experiments' => array(
                'type' => 'experiments',
                'value' => array($target->id),
                'value_labels' => array('Old label'),
                'allow_multi_values' => true,
            ),
        ));

        [$path, $metadataPath, $crate] = $this->export(array($source));

        try {
            foreach ($crate['@graph'] as &$node) {
                if (($node['propertyID'] ?? '') === 'Missing experiments') {
                    $node['value'] = array(
                        array('@id' => './not-in-the-archive/'),
                    );
                }
            }
            unset($node);

            $this->replaceMetadata($path, $metadataPath, $crate);

            $imported = $this->importedByTitle($this->import($path));

            $field = $this->metadata(
                $imported['ELN metadata missing source'],
            )['Missing experiments'];

            $this->assertSame(array(), $field['value']);
            $this->assertArrayNotHasKey('value_labels', $field);
        } finally {
            unlink($path);
        }
    }

    public function testLegacyNumericReferenceDoesNotCrashImport(): void
    {
        $target = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata legacy target',
        );

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata legacy source',
        );

        $this->setMetadata($source, array(
            'Legacy experiment' => array(
                'type' => 'experiments',
                'value' => $target->id,
            ),
        ));

        [$path, $metadataPath, $crate] = $this->export(array($source));

        try {
            // Old PropertyValue nodes contained SQL IDs, not @id references.
            foreach ($crate['@graph'] as &$node) {
                if (($node['propertyID'] ?? '') === 'Legacy experiment') {
                    $node['value'] = $target->id;
                }
            }
            unset($node);

            $this->replaceMetadata($path, $metadataPath, $crate);

            $imported = $this->importedByTitle($this->import($path));

            $this->assertArrayHasKey(
                'Legacy experiment',
                $this->metadata($imported['ELN metadata legacy source']),
            );
        } finally {
            unlink($path);
        }
    }

    public function testUserReferenceUsesRequesterForUntrustedImport(): void
    {
        $otherUser = $this->getRandomUserInTeam(1);

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata user source',
        );

        $this->setMetadata($source, array(
            'User' => array(
                'type' => 'users',
                'value' => $otherUser->getUserid(),
            ),
        ));

        [$path] = $this->export(array($source));

        try {
            $imported = $this->importedByTitle($this->import($path));

            $field = $this->metadata(
                $imported['ELN metadata user source'],
            )['User'];

            $this->assertSame(
                $this->requester->getUserid(),
                $field['value'],
            );
        } finally {
            unlink($path);
        }
    }

    public function testTrustedImportResolvesReferencedUser(): void
    {
        $otherUser = $this->getRandomUserInTeam(1);

        $source = $this->createEntity(
            EntityType::Experiments,
            'ELN metadata trusted user source',
        );

        $this->setMetadata($source, array(
            'User' => array(
                'type' => 'users',
                'value' => $otherUser->getUserid(),
            ),
        ));

        [$path] = $this->export(array($source));

        try {
            $imported = $this->importedByTitle(
                $this->import($path, trusted: true),
            );

            $field = $this->metadata(
                $imported['ELN metadata trusted user source'],
            )['User'];

            $this->assertSame(
                $otherUser->getUserid(),
                $field['value'],
            );
        } finally {
            unlink($path);
        }
    }

    private function createEntity(EntityType $type, string $title): AbstractEntity
    {
        $Entity = $type->toInstance($this->requester);
        $Entity->setId($Entity->create());
        $Entity->update(new EntityParams('title', $title));
        return $Entity;
    }

    private function setMetadata(AbstractEntity $Entity, array $fields): void
    {
        $Entity->update(new EntityParams(
            'metadata',
            json_encode(array('extra_fields' => $fields), JSON_THROW_ON_ERROR),
        ));
    }

    private function export(array $entities, ?Compounds $Compounds = null): array
    {
        foreach ($entities as $Entity) {
            $Entity->readOne();
        }

        $Maker = new MakeEln(
            $this->logger,
            $this->createStub(ZipStream::class),
            $this->requester,
            $entities,
            new Instance2Rors(),
            new Teams2Rors(1),
            new Users2Rors(1),
            includeLinkedEntities: false,
            Compounds: $Compounds,
        );

        $path = tempnam(sys_get_temp_dir(), 'elabftw-eln-metadata-');
        $this->assertIsString($path);

        $Maker->writeToFile($path);

        $metadataPath = basename(
            $Maker->getFileName(),
            '.eln',
        ) . '/ro-crate-metadata.json';

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));

        $json = $zip->getFromName($metadataPath);
        $this->assertIsString($json);
        $zip->close();

        return array(
            $path,
            $metadataPath,
            json_decode($json, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function replaceMetadata(
        string $path,
        string $metadataPath,
        array $crate,
    ): void {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $this->assertTrue($zip->addFromString(
            $metadataPath,
            json_encode($crate, JSON_THROW_ON_ERROR),
        ));
        $this->assertTrue($zip->close());
    }

    private function import(
        string $path,
        ?Compounds $Compounds = null,
        bool $trusted = false,
    ): Eln {
        $ImporterClass = $trusted ? TrustedEln::class : Eln::class;

        $Import = new $ImporterClass(
            $this->requester,
            $this->requester,
            new UploadedFile($path, 'metadata.eln', test: true),
            $this->fs,
            $this->logger,
            Compounds: $Compounds,
        );

        $Import->import();
        return $Import;
    }

    private function importedByTitle(Eln $Import): array
    {
        $inserted = new ReflectionProperty(
            Eln::class,
            'insertedEntities',
        )->getValue($Import);

        $entities = array();

        foreach ($inserted as $entry) {
            $Entity = $entry['entity_type']->toInstance(
                $this->requester,
                $entry['id'],
            );

            $data = $Entity->readOne();
            $entities[$data['title']] = $Entity;
        }

        return $entities;
    }

    private function metadata(AbstractEntity $Entity): array
    {
        $data = $Entity->readOne();

        return json_decode(
            $data['metadata'],
            true,
            512,
            JSON_THROW_ON_ERROR,
        )['extra_fields'];
    }

    private function fieldNode(array $crate, string $name): array
    {
        foreach ($crate['@graph'] as $node) {
            if (
                ($node['@type'] ?? '') === 'PropertyValue'
                && ($node['propertyID'] ?? '') === $name
            ) {
                return $node;
            }
        }

        self::fail('Missing field ' . $name);
    }
}
