<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\ExtraFieldReference;
use Elabftw\Enums\GhsSignalWord;
use Elabftw\Enums\Storage;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\CompoundsFactory;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Storage\Cache\ElabCache;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use ZipArchive;

use function array_column;
use function dirname;
use function getenv;
use function json_decode;
use function json_encode;
use function putenv;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

class ElnTest extends \PHPUnit\Framework\TestCase
{
    private FilesystemOperator $fs;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        // can't use InMemory adapter here because of ziparchive
        $this->fs = new ElabCache()->getFs();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testFailUploadedFile(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_INI_SIZE, // file is too big!
            true,
        );

        $this->expectException(ImproperActionException::class);
        new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
    }

    public function testWrongMime(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/universign.asn1',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $this->expectException(ImproperActionException::class);
        new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
    }

    /**
     * @dataProvider unsafeArchivePathProvider
     */
    public function testRejectUnsafeArchivePath(string $unsafePath): void
    {
        $exportsFs = Storage::EXPORTS->getStorage()->getFs();
        $target = Tools::getUuidv4();
        $exportsFs->write($target, 'original export');

        $archivePath = tempnam(sys_get_temp_dir(), 'elabftw-unsafe-eln-');
        $this->assertIsString($archivePath);
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($archivePath, ZipArchive::OVERWRITE));
        $this->assertTrue($archive->addFromString('crate/ro-crate-metadata.json', '{"@graph":[]}'));
        $this->assertTrue($archive->addFromString(sprintf($unsafePath, $target), 'poisoned export'));
        $this->assertTrue($archive->close());

        $uploadedFile = new UploadedFile(
            $archivePath,
            'unsafe.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        try {
            $Import = new Eln(
                new Users(1, 1),
                new Users(1, 1),
                $uploadedFile,
                $exportsFs,
                $this->logger,
                CompoundsFactory::getCompounds(new Users(1, 1)),
                EntityType::Experiments,
            );
            $Import->prepare();
            $this->fail('An ELN archive containing an unsafe path was accepted.');
        } catch (ImproperActionException $e) {
            $this->assertSame('The archive contains an unsafe path.', $e->getMessage());
            $this->assertSame('original export', $exportsFs->read($target));
        } finally {
            $exportsFs->delete($target);
            unlink($archivePath);
        }
    }

    public static function unsafeArchivePathProvider(): array
    {
        return array(
            'parent traversal' => array('../%s'),
            'nested parent traversal' => array('attachments/../../%s'),
            'backslash traversal' => array('..\\%s'),
            'absolute path' => array('/%s'),
            'Windows drive path' => array('C:/%s'),
            'encoded traversal' => array('%%2e%%2e/%%2f%s'),
            'double encoded traversal' => array('%%252e%%252e/%%252f%s'),
        );
    }

    public function testImportExperiments(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testSanitizeImportedChangelogHtml(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );
        $actions = array(
            array(
                '@type' => 'UpdateAction',
                'startTime' => '2026-08-22T12:00:00+00:00',
                'object' => 'body',
                'result' => '<p>safe content</p><img src="gopher://127.0.0.1:39091/_payload">',
            ),
            array(
                '@type' => 'UpdateAction',
                'startTime' => '2026-08-22T12:00:00+00:00',
                'object' => 'title',
                'result' => '<img src="gopher://127.0.0.1:39091/_payload">',
            ),
        );

        $Method = new ReflectionMethod($Import, 'updateActionsToChangelog');
        /** @var array<array{content: string}> $changelog */
        $changelog = $Method->invoke($Import, $actions);

        $this->assertStringContainsString('<p>safe content</p>', $changelog[0]['content']);
        $this->assertStringNotContainsString('gopher:', $changelog[0]['content']);
        // Non-HTML targets are escaped by Twig and must retain their original value.
        $this->assertSame($actions[1]['result'], $changelog[1]['content']);
    }

    public function testImportV103(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/version-103.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());

        // run it a second time so we trigger the custom_id exception
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/version-103.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testImportV104(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/version-104.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testImportV106(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/version-106.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );
        $Import->import();
        // it contains a linked resource
        $this->assertEquals(2, $Import->getInserted());
    }

    public function testImportTrusted(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        // Invalid web limit configuration must not affect CLI imports.
        $previous = getenv('IMPORT_MAX_TOTAL_BYTES');
        putenv('IMPORT_MAX_TOTAL_BYTES=0');
        try {
            $Import = new TrustedEln(
                new Users(1, 1),
                new Users(1, 1),
                $uploadedFile,
                $this->fs,
                $this->logger,
                CompoundsFactory::getCompounds(new Users(1, 1)),
                EntityType::Experiments,
                category: 1,
            );
            $Import->import();
            $this->assertEquals(1, $Import->getInserted());
        } finally {
            putenv($previous === false ? 'IMPORT_MAX_TOTAL_BYTES' : 'IMPORT_MAX_TOTAL_BYTES=' . $previous);
        }
    }

    public function testTrustedMetadataUserReference(): void
    {
        $Users = new Users(1, 1);
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $Import = new TrustedEln(
            $Users,
            $Users,
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds($Users),
        );

        new ReflectionProperty(Eln::class, 'graph')->setValue($Import, array(
            array('@id' => '#user', 'email' => $Users->userData['email']),
            array('@id' => '#invalid-user'),
        ));

        $Method = new ReflectionMethod(TrustedEln::class, 'getMetadataUserReference');

        // Empty reference: use the parent method.
        $this->assertSame(0, $Method->invoke($Import, ''));

        // Existing user: return their ID.
        $this->assertSame($Users->getUserid(), $Method->invoke($Import, '#user'));

        // Missing email: catch the exception and return 0.
        $this->assertSame(0, $Method->invoke($Import, '#invalid-user'));
    }

    public function testForcedTypeKeepsTypeOfMetadataReferenceTargets(): void
    {
        $Import = $this->getElnForReflection(EntityType::Experiments);
        new ReflectionProperty(Eln::class, 'graph')->setValue($Import, array(
            array('@id' => '#pv-items', '@type' => 'PropertyValue', 'valueReference' => 'items', 'value' => array('@id' => './resource-b/')),
            array('@id' => '#pv-multi', '@type' => 'PropertyValue', 'valueReference' => 'experiments', 'value' => array(array('@id' => './experiment-c/'))),
            // referenced with both types: the forced type applies
            array('@id' => '#pv-x1', '@type' => 'PropertyValue', 'valueReference' => 'items', 'value' => array('@id' => './ambiguous/')),
            array('@id' => '#pv-x2', '@type' => 'PropertyValue', 'valueReference' => 'experiments', 'value' => array('@id' => './ambiguous/')),
        ));
        new ReflectionMethod(Eln::class, 'collectMetadataTargetTypes')->invoke($Import);
        $GetEntityType = new ReflectionMethod(Eln::class, 'getEntityType');

        $this->assertSame(EntityType::Items, $GetEntityType->invoke($Import, array('@id' => './resource-b/', 'genre' => 'experiment')));
        $this->assertSame(EntityType::Experiments, $GetEntityType->invoke($Import, array('@id' => './experiment-c/')));
        $this->assertSame(EntityType::Experiments, $GetEntityType->invoke($Import, array('@id' => './ambiguous/')));
        $this->assertSame(EntityType::Experiments, $GetEntityType->invoke($Import, array('@id' => './not-referenced/', 'genre' => 'resource')));
    }

    public function testResolveMetadataReferencesChecksTypeAndKeepsLabelsAligned(): void
    {
        $Users = new Users(1, 1);
        $Import = $this->getElnForReflection(EntityType::Experiments);
        $Entity = new Experiments($Users);
        $Entity->setId($Entity->create());
        $Target = new Experiments($Users);
        $targetId = $Target->create();
        $Entity->update(new EntityParams('metadata', json_encode(array(
            'extra_fields' => array(
                'linked' => array('type' => 'experiments', 'allow_multi_values' => true, 'value' => array(123, 456), 'value_labels' => array('Control', 'Sample')),
                'resource' => array('type' => 'items', 'value' => 789, 'value_labels' => array('Some resource')),
            ),
        ), JSON_THROW_ON_ERROR)));

        new ReflectionProperty(Eln::class, 'insertedEntities')->setValue($Import, array(
            array('item_@id' => './experiment-b/', 'id' => $targetId, 'entity_type' => EntityType::Experiments, 'users' => $Users),
        ));
        new ReflectionProperty(Eln::class, 'metadataReferencesToResolve')->setValue($Import, array(
            // the first value was not exported: its label must go away with it
            array('entity_type' => EntityType::Experiments, 'id' => $Entity->id, 'users' => $Users, 'field' => 'linked', 'type' => ExtraFieldReference::Experiments, 'targets' => array(1 => './experiment-b/'), 'is_multiple' => true),
            // the target was imported as an experiment: it cannot fill an items field
            array('entity_type' => EntityType::Experiments, 'id' => $Entity->id, 'users' => $Users, 'field' => 'resource', 'type' => ExtraFieldReference::Items, 'targets' => array(0 => './experiment-b/'), 'is_multiple' => false),
        ));
        new ReflectionMethod(Eln::class, 'resolveMetadataReferences')->invoke($Import);

        $Entity = new Experiments($Users, $Entity->id);
        $extraFields = json_decode($Entity->entityData['metadata'], true, 512, JSON_THROW_ON_ERROR)['extra_fields'];
        $this->assertEquals(array($targetId), $extraFields['linked']['value']);
        $this->assertSame(array('Sample'), $extraFields['linked']['value_labels']);
        $this->assertEmpty($extraFields['resource']['value']);
        $this->assertEmpty($extraFields['resource']['value_labels'] ?? null);
    }

    public function testResolveMetadataReferencesIgnoresUnknownField(): void
    {
        $Users = new Users(1, 1);
        $Import = $this->getElnForReflection(null);
        $Entity = new Experiments($Users);
        $Entity->setId($Entity->create());
        $metadata = json_encode(array(
            'extra_fields' => array(
                'known' => array('type' => 'text', 'value' => 'untouched'),
            ),
        ), JSON_THROW_ON_ERROR);
        $Entity->update(new EntityParams('metadata', $metadata));

        new ReflectionProperty(Eln::class, 'metadataReferencesToResolve')->setValue($Import, array(
            array('entity_type' => EntityType::Experiments, 'id' => $Entity->id, 'users' => $Users, 'field' => 'absent', 'type' => ExtraFieldReference::Users, 'targets' => array(0 => ''), 'is_multiple' => false),
        ));
        new ReflectionMethod(Eln::class, 'resolveMetadataReferences')->invoke($Import);

        $Entity = new Experiments($Users, $Entity->id);
        $this->assertJsonStringEqualsJsonString($metadata, $Entity->entityData['metadata']);
    }

    public function testResolveMetadataReferenceToItselfIsCleared(): void
    {
        $Users = new Users(1, 1);
        $Import = $this->getElnForReflection(null);
        $Entity = new Experiments($Users);
        $Entity->setId($Entity->create());
        $Entity->update(new EntityParams('metadata', json_encode(array(
            'extra_fields' => array(
                'self' => array('type' => 'experiments', 'value' => 999999999),
            ),
        ), JSON_THROW_ON_ERROR)));

        new ReflectionProperty(Eln::class, 'insertedEntities')->setValue($Import, array(
            array('item_@id' => './self/', 'id' => $Entity->id, 'entity_type' => EntityType::Experiments, 'users' => $Users),
        ));
        // an entry cannot link to itself: the source id must not stay in the field
        new ReflectionProperty(Eln::class, 'metadataReferencesToResolve')->setValue($Import, array(
            array('entity_type' => EntityType::Experiments, 'id' => $Entity->id, 'users' => $Users, 'field' => 'self', 'type' => ExtraFieldReference::Experiments, 'targets' => array(0 => './self/'), 'is_multiple' => false),
        ));
        new ReflectionMethod(Eln::class, 'resolveMetadataReferences')->invoke($Import);

        $Entity = new Experiments($Users, $Entity->id);
        $extraFields = json_decode($Entity->entityData['metadata'], true, 512, JSON_THROW_ON_ERROR)['extra_fields'];
        $this->assertSame('', $extraFields['self']['value']);
    }

    public function testMetadataReferencesOnlyForRecentArchives(): void
    {
        $Import = $this->getElnForReflection(null);
        $Version = new ReflectionProperty(Eln::class, 'internalElnVersion');
        $HasMetadataReferences = new ReflectionMethod(Eln::class, 'hasMetadataReferences');
        // archives from other software have no version
        $Version->setValue($Import, -1);
        $this->assertFalse($HasMetadataReferences->invoke($Import));
        // older eLabFTW archives hold database ids
        $Version->setValue($Import, 108);
        $this->assertFalse($HasMetadataReferences->invoke($Import));
        $Version->setValue($Import, 109);
        $this->assertTrue($HasMetadataReferences->invoke($Import));
    }

    public function testImportExperimentsMulti(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/multiple-experiments.eln',
            'importable-multi.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(9, $Import->getInserted());
    }

    public function testImportItems(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/single-experiment.eln',
            'importable.items.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testImportNoJson(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-nojson.zip',
            'importable-nojson.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $this->expectException(ImproperActionException::class);
        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
            category: 1,
        );
        $Import->prepare();
    }

    public function testInvalidShasum(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/invalid-shasum.eln',
            'invalid-shasum.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
    }

    public function testImportBeforeV5(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/4.9.0_special_chars.eln',
            '4.9.0_special_chars.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(2, $Import->getInserted());
    }

    public function testImportPasta(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/PASTA.eln',
            'PASTA.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(9, $Import->getInserted());
    }

    public function testImportSampledb(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/sampledb.eln',
            'sampledb.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(2, $Import->getInserted());
    }

    public function testImportBenchLineage(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/benchlineage.eln',
            'benchlineage.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
        );
        $Import->import();
        $this->assertSame(1, $Import->getInserted());

        $Experiment = $this->getExperimentFromTitle('Power-conversion and RC-filter characterization');
        $this->assertCount(20, $Experiment->entityData['uploads']);
        $this->assertEqualsCanonicalizing(array(
            'benchlineage.json',
            'buck-efficiency.json',
            'buck-load-001.json',
            'buck-load-001.json',
            'buck-load-sweep.csv',
            'cal-dmm-01-2026.json',
            'cal-scope-01-2026.json',
            'cal-source-01-2026.json',
            'demo-report.html',
            'dmm-01.json',
            'rc-baseline-001.json',
            'rc-baseline-001.json',
            'rc-baseline.csv',
            'rc-filter.json',
            'rc-resistor-swap.csv',
            'rc-swap-002.json',
            'rc-swap-002.json',
            'scope-01.json',
            'seal-2026-08-05T080000-0000.json',
            'source-01.json',
        ), array_column($Experiment->entityData['uploads'], 'real_name'));
    }

    // a crate from another tool: flattened as RO-Crate requires, so variableMeasured only holds references,
    // and no eLabFTW "version" on the root node
    public function testImportFlattenedMetadataWithoutVersion(): void
    {
        $title = 'Flattened metadata without version';
        $crate = array(
            '@context' => 'https://w3id.org/ro/crate/1.2/context',
            '@graph' => array(
                array(
                    '@id' => 'ro-crate-metadata.json',
                    '@type' => 'CreativeWork',
                    'about' => array('@id' => './'),
                    'conformsTo' => array('@id' => 'https://w3id.org/ro/crate/1.2'),
                ),
                array(
                    '@id' => './',
                    '@type' => 'Dataset',
                    'name' => 'Export from another tool',
                    'hasPart' => array(array('@id' => './experiment/')),
                ),
                array(
                    '@id' => './experiment/',
                    '@type' => 'Dataset',
                    'name' => $title,
                    'variableMeasured' => array(
                        array('@id' => '#viscosity'),
                        array('@id' => '#elabftw-metadata'),
                    ),
                ),
                array(
                    '@id' => '#viscosity',
                    '@type' => 'PropertyValue',
                    'propertyID' => 'viscosity',
                    'value' => 2300,
                    'unitText' => 'mPa.s',
                ),
                array(
                    '@id' => '#elabftw-metadata',
                    '@type' => 'PropertyValue',
                    'propertyID' => 'elabftw_metadata',
                    'value' => '{"extra_fields": {"Viscosity": {"type": "text", "value": "2300"}}}',
                ),
            ),
        );

        $archivePath = tempnam(sys_get_temp_dir(), 'elabftw-flattened-eln-');
        $this->assertIsString($archivePath);
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($archivePath, ZipArchive::OVERWRITE));
        $json = json_encode($crate, JSON_THROW_ON_ERROR);
        $this->assertTrue($archive->addFromString('flattened-crate/ro-crate-metadata.json', $json));
        $this->assertTrue($archive->close());

        $uploadedFile = new UploadedFile(
            $archivePath,
            'flattened.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        try {
            $Import = new Eln(
                new Users(1, 1),
                new Users(1, 1),
                $uploadedFile,
                $this->fs,
                $this->logger,
                CompoundsFactory::getCompounds(new Users(1, 1)),
                EntityType::Experiments,
            );
            $Import->import();
            $this->assertSame(1, $Import->getInserted());

            $Experiment = $this->getExperimentFromTitle($title);
            $this->assertIsString($Experiment->entityData['metadata']);
            $metadata = json_decode($Experiment->entityData['metadata'], true);
            $this->assertSame('2300', $metadata['extra_fields']['Viscosity']['value']);
        } finally {
            unlink($archivePath);
        }
    }

    // test import keeps the state for archived/locked/deleted entries
    public function testImportRestoresLifecycle(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/locked-archived-deleted-experiments.eln',
            'locked-archived-deleted-experiments.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Experiments,
        );

        $Import->import();

        $this->assertGreaterThanOrEqual(3, $Import->getInserted());

        $Locked = $this->getExperimentFromTitle('Locked');
        $this->assertSame(State::Normal->value, $Locked->entityData['state']);
        $this->assertSame(1, $Locked->entityData['locked']);

        $Archived = $this->getExperimentFromTitle('Archived');
        $this->assertSame(State::Archived->value, $Archived->entityData['state']);
        $this->assertSame(1, $Archived->entityData['locked']);

        $Deleted = $this->getExperimentFromTitle('Deleted');
        $this->assertSame(State::Deleted->value, $Deleted->entityData['state']);
    }

    public function testImportCompounds(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/with-compounds.eln',
            'compounds.eln',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Eln(
            new Users(1, 1),
            new Users(1, 1),
            $uploadedFile,
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds(new Users(1, 1)),
            EntityType::Items,
            category: 1,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testCompoundGhsRoundTrip(): void
    {
        // what MakeEln writes for a compound
        $properties = new ReflectionMethod(MakeEln::class, 'ghsToPropertyValues')->invoke(
            new ReflectionClass(MakeEln::class)->newInstanceWithoutConstructor(),
            array(
                'ghs_signal_word' => 'Danger',
                'ghs_statements' => array(
                    array('id' => 1, 'code' => 'H225', 'kind' => 'H', 'statement' => 'Highly flammable liquid and vapour.', 'is_deprecated' => 0),
                    array('id' => 2, 'code' => 'EUH066', 'kind' => 'EUH', 'statement' => 'Repeated exposure may cause skin dryness or cracking.', 'is_deprecated' => 0),
                    array('id' => 3, 'code' => 'P301+P316', 'kind' => 'P', 'statement' => 'IF SWALLOWED: Get emergency medical help immediately.', 'is_deprecated' => 0),
                ),
            ),
        );
        $this->assertSame(Compound::ELN_GHS_SIGNAL_WORD, $properties[0]['propertyID']);
        $this->assertSame(Compound::ELN_GHS_HAZARD_STATEMENT, $properties[2]['propertyID']);
        $this->assertSame(Compound::ELN_GHS_PRECAUTIONARY_STATEMENT, $properties[3]['propertyID']);
        // other software can add properties of its own
        $properties[] = array('@type' => 'PropertyValue', 'propertyID' => 'Boiling point', 'value' => 'H300');
        $properties[] = 'not a property';

        $compound = new ReflectionMethod(Eln::class, 'compoundFromJsonLd')->invoke(
            $this->getElnForReflection(EntityType::Experiments),
            array('@id' => '#compound-1', '@type' => 'MolecularEntity', 'name' => 'Ethanol', 'additionalProperty' => $properties),
        );
        $this->assertSame(GhsSignalWord::Danger, $compound->ghsSignalWord);
        $this->assertSame(array('H225', 'EUH066', 'P301+P316'), $compound->ghsStatements);
        // nothing to export without GHS data
        $this->assertSame(array(), new ReflectionMethod(MakeEln::class, 'ghsToPropertyValues')->invoke(
            new ReflectionClass(MakeEln::class)->newInstanceWithoutConstructor(),
            array('ghs_signal_word' => null),
        ));
    }

    private function getExperimentFromTitle(string $title): Experiments
    {
        $Experiments = new Experiments(new Users(1, 1));
        // search by title & include Archived entries
        $query = new InputBag(array(
            'q' => sprintf('title:"%s"', $title),
            'state' => '1,2,3',
        ));
        $DisplayParams = new DisplayParams(new Users(1, 1), EntityType::Experiments, $query);
        $all = $Experiments->readAll($DisplayParams);

        foreach ($all as $entry) {
            if ($entry['title'] === $title) {
                $Experiments->setId($entry['id']);
                return $Experiments;
            }
        }
        $this->fail(sprintf('Could not find imported experiment with title "%s".', $title));
    }

    private function getElnForReflection(?EntityType $entityType): Eln
    {
        $Users = new Users(1, 1);
        return new Eln(
            $Users,
            $Users,
            new UploadedFile(dirname(__DIR__, 2) . '/_data/single-experiment.eln', 'importable.eln', null, UPLOAD_ERR_OK, true),
            $this->fs,
            $this->logger,
            CompoundsFactory::getCompounds($Users),
            $entityType,
        );
    }
}
