<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Factories\CompoundsFactory;
use Elabftw\Make\MakeEln;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\StepGroups;
use Elabftw\Models\Steps;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\UploadGroups;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Storage\Cache\ElabCache;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use ZipStream\ZipStream;

use function array_column;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_values;
use function basename;
use function dirname;
use function json_decode;
use function json_encode;
use function random_int;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ElnGroupsTest extends TestCase
{
    private FilesystemOperator $fs;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->fs = new ElabCache()->getFs();
        $this->logger = $this->createStub(LoggerInterface::class);
    }

    public static function entityTypes(): array
    {
        return array(
            'experiment' => array(EntityType::Experiments),
            'resource' => array(EntityType::Items),
            'experiment template' => array(EntityType::Templates),
            'resource template' => array(EntityType::ItemsTypes),
        );
    }

    /**
     * @dataProvider entityTypes
     */
    public function testGroupsRoundTrip(EntityType $type): void
    {
        $Source = $this->createEntity($type);
        $this->addGroupedContent($Source);
        [$path, $metadataPath, $crate] = $this->export(array($Source));

        try {
            $nodes = array_column($crate['@graph'], null, '@id');
            $sections = array_values(array_filter($nodes, static fn(array $node): bool => $node['@type'] === 'HowToSection'));
            $collections = array_values(array_filter($nodes, static fn(array $node): bool => $node['@type'] === 'Collection'));
            $this->assertCount(3, $sections);
            $this->assertCount(3, $collections);
            $this->assertSame('108', $nodes['./']['version']);
            $this->assertSame(array('Analysis', 'Preparation & µ', 'Empty steps'), array_column($sections, 'name'));
            $this->assertSame(array('Processed data', 'Raw data', 'Empty uploads'), array_column($collections, 'name'));
            $this->assertSame(array(), $sections[2]['itemListElement']);
            $this->assertSame(array(), $collections[2]['hasPart']);

            foreach (array_merge($sections, $collections) as $group) {
                $dataset = $nodes[$group['isPartOf']['@id']];
                $this->assertContains(array('@id' => $group['@id']), $dataset['hasPart']);
                foreach ($group[$group['@type'] === 'HowToSection' ? 'itemListElement' : 'hasPart'] as $reference) {
                    $member = $nodes[$reference['@id']];
                    $this->assertSame(array('@id' => $group['@id']), $member['isPartOf']);
                    // Existing flat references are retained for older importers.
                    $this->assertContains($reference, $dataset[$group['@type'] === 'HowToSection' ? 'step' : 'hasPart']);
                }
            }

            // Graph order, JSON property order and member-reference order must not affect grouping or display order.
            foreach ($crate['@graph'] as &$node) {
                if ($node['@type'] === 'Dataset' && $node['@id'] !== './') {
                    $node['hasPart'] = array_reverse($node['hasPart']);
                    $node['step'] = array_reverse($node['step']);
                    $node = array_merge(array('step' => $node['step']), $node);
                }
                if ($node['@type'] === 'Collection') {
                    $node['hasPart'] = array_reverse($node['hasPart']);
                }
            }
            unset($node);
            $crate['@graph'] = array_reverse($crate['@graph']);
            $this->replaceMetadata($path, $metadataPath, $crate);
            $Target = $this->import($path)[0];

            $this->assertSame($type, $Target->entityType);
            $this->assertSame($this->content($Source), $this->content($Target));
            $this->assertNotSame(array_column(new StepGroups($Source)->readAll(), 'id'), array_column(new StepGroups($Target)->readAll(), 'id'));
            $this->assertNotSame(array_column(new UploadGroups($Source)->readAll(), 'id'), array_column(new UploadGroups($Target)->readAll(), 'id'));
        } finally {
            unlink($path);
        }
    }

    public function testImportUsesGroupMemberReferences(): void
    {
        $Source = $this->createEntity(EntityType::Experiments);
        $this->addGroupedContent($Source);
        [$path, $metadataPath, $crate] = $this->export(array($Source));

        try {
            $nodes = array_column($crate['@graph'], null, '@id');
            foreach ($crate['@graph'] as &$node) {
                if ($node['@type'] === 'Dataset' && $node['@id'] !== './') {
                    // Grouped files are reachable through the collection only.
                    $node['hasPart'] = array_values(array_filter($node['hasPart'], static function (array $reference) use ($nodes): bool {
                        $part = $nodes[$reference['@id']];
                        return $part['@type'] !== 'File' || !isset($part['isPartOf']);
                    }));
                }
                if ($node['@type'] === 'File' || $node['@type'] === 'HowToStep') {
                    unset($node['isPartOf']);
                }
            }
            unset($node);
            $this->replaceMetadata($path, $metadataPath, $crate);
            $Target = $this->import($path)[0];
            $this->assertSame($this->content($Source), $this->content($Target));
        } finally {
            unlink($path);
        }
    }

    public function testEmptyGroupsRoundTrip(): void
    {
        $Source = $this->createEntity(EntityType::Experiments);
        new StepGroups($Source)->postAction(Action::Create, array('title' => 'Empty steps'));
        new UploadGroups($Source)->postAction(Action::Create, array('title' => 'Empty uploads'));
        [$path] = $this->export(array($Source));

        try {
            $Target = $this->import($path)[0];
            $this->assertSame($this->content($Source), $this->content($Target));
        } finally {
            unlink($path);
        }
    }

    public function testGroupsAreScopedToEachDataset(): void
    {
        $sources = array($this->createEntity(EntityType::Experiments), $this->createEntity(EntityType::Items));
        foreach ($sources as $Source) {
            $this->addGroupedContent($Source);
        }
        [$path, $metadataPath, $crate] = $this->export($sources);

        try {
            $nodes = array_column($crate['@graph'], null, '@id');
            $datasets = array_values(array_filter($nodes, static fn(array $node): bool => $node['@type'] === 'Dataset' && $node['@id'] !== './'));
            $foreignGroups = array_values(array_filter($datasets[1]['hasPart'], static fn(array $reference): bool => $nodes[$reference['@id']]['@type'] === 'Collection' || $nodes[$reference['@id']]['@type'] === 'HowToSection'));
            foreach ($crate['@graph'] as &$node) {
                if ($node['@id'] === $datasets[0]['@id']) {
                    $node['hasPart'] = array_merge($node['hasPart'], $foreignGroups);
                }
            }
            unset($node);
            $this->replaceMetadata($path, $metadataPath, $crate);
            $targets = $this->import($path);
            $this->assertCount(2, $targets);
            foreach ($sources as $index => $Source) {
                $this->assertSame($this->content($Source), $this->content($targets[$index]));
            }
        } finally {
            unlink($path);
        }
    }

    public function testMetadataReferences(): void
    {
        $firstExp = $this->createEntity(EntityType::Experiments);
        $secondExp = $this->createEntity(EntityType::Experiments);
        $Resource = $this->createEntity(EntityType::Items);
        $exportedExp = $this->createEntity(EntityType::Experiments);
        $Compounds = CompoundsFactory::create(new Users(1, 1));
        $compoundId = $Compounds->createFromCompound(new Compound(
            cid: random_int(100000000, 999999999),
            name: 'Test compound',
            molecularWeight: 18.0,
        ));

        foreach (array(
            'firstExp' => $firstExp,
            'secondExp' => $secondExp,
            'Resource' => $Resource,
            'exportedExp' => $exportedExp,
        ) as $title => $entity) {
            $entity->patch(Action::Update, array('title' => $title));
        }

        $exportedExp->patch(Action::Update, array('metadata' => json_encode(array(
            'extra_fields' => array(
                'Experiment' => array('type' => 'experiments', 'value' => $firstExp->id),
                'Experiments' => array(
                    'type' => 'experiments',
                    'value' => array($secondExp->id, $firstExp->id),
                    'allow_multi_values' => true,
                ),
                'Resource' => array('type' => 'items', 'value' => $Resource->id),
                'Compound' => array('type' => 'compounds', 'value' => $compoundId),
            ),
        ), JSON_THROW_ON_ERROR)));

        [$path] = $this->export(array($exportedExp));

        try {
            $imported = array();
            foreach ($this->import($path) as $entity) {
                $imported[$entity->readOne()['title']] = $entity;
            }

            $fields = json_decode($imported['exportedExp']->readOne()['metadata'], true, 512, JSON_THROW_ON_ERROR)['extra_fields'];

            $this->assertNotSame($firstExp->id, $imported['firstExp']->id);
            $this->assertSame($imported['firstExp']->id, $fields['Experiment']['value']);
            $this->assertSame(
                array($imported['secondExp']->id, $imported['firstExp']->id),
                $fields['Experiments']['value'],
            );
            $this->assertSame($imported['Resource']->id, $fields['Resource']['value']);
            $this->assertSame($compoundId, $fields['Compound']['value']);
        } finally {
            unlink($path);
        }
    }

    private function createEntity(EntityType $type): AbstractEntity
    {
        $Entity = $type->toInstance(new Users(1, 1));
        $Entity->setId($Entity->create());
        return $Entity;
    }

    private function addGroupedContent(AbstractEntity $Entity): void
    {
        $StepGroups = new StepGroups($Entity);
        $firstStepGroup = $StepGroups->postAction(Action::Create, array('title' => 'Preparation & µ'));
        $secondStepGroup = $StepGroups->postAction(Action::Create, array('title' => 'Analysis'));
        $emptyStepGroup = $StepGroups->postAction(Action::Create, array('title' => 'Empty steps'));
        $StepGroups->patch(Action::Update, array('ordering' => array($secondStepGroup, $firstStepGroup, $emptyStepGroup)));
        $Steps = new Steps($Entity);
        $defaultStep = $Steps->postAction(Action::Create, array('body' => 'General checks'));
        $firstStep = $Steps->postAction(Action::Create, array('body' => 'Prepare sample A', 'group_id' => $firstStepGroup));
        $secondStep = $Steps->postAction(Action::Create, array('body' => 'Prepare sample B', 'group_id' => $firstStepGroup));
        $analysisStep = $Steps->postAction(Action::Create, array('body' => 'Analyze sample', 'group_id' => $secondStepGroup));
        new Steps($Entity, $firstStep)->patch(Action::Finish, array());
        $Steps->patch(Action::Update, array('grouped_ordering' => array(
            array('group_id' => null, 'step_ids' => array($defaultStep)),
            array('group_id' => $firstStepGroup, 'step_ids' => array($secondStep, $firstStep)),
            array('group_id' => $secondStepGroup, 'step_ids' => array($analysisStep)),
        )));

        $UploadGroups = new UploadGroups($Entity);
        $firstUploadGroup = $UploadGroups->postAction(Action::Create, array('title' => 'Raw data'));
        $secondUploadGroup = $UploadGroups->postAction(Action::Create, array('title' => 'Processed data'));
        $emptyUploadGroup = $UploadGroups->postAction(Action::Create, array('title' => 'Empty uploads'));
        $UploadGroups->patch(Action::Update, array('ordering' => array($secondUploadGroup, $firstUploadGroup, $emptyUploadGroup)));
        $imagePath = dirname(__DIR__, 2) . '/_data/example.png';
        $defaultUpload = $Entity->Uploads->create(new CreateUploadFromLocalFile('default.png', $imagePath));
        $firstUpload = $Entity->Uploads->create(new CreateUploadFromLocalFile('shared.png', $imagePath), groupId: $firstUploadGroup);
        $secondUpload = $Entity->Uploads->create(new CreateUploadFromLocalFile('second.png', $imagePath), groupId: $firstUploadGroup);
        $archivedUpload = $Entity->Uploads->create(new CreateUploadFromLocalFile('shared.png', $imagePath), groupId: $secondUploadGroup);
        new Uploads($Entity, $archivedUpload)->patch(Action::Archive, array());
        $Entity->Uploads->patch(Action::Update, array('grouped_ordering' => array(
            array('group_id' => null, 'upload_ids' => array($defaultUpload)),
            array('group_id' => $firstUploadGroup, 'upload_ids' => array($secondUpload, $firstUpload)),
            array('group_id' => $secondUploadGroup, 'upload_ids' => array($archivedUpload)),
        )));
    }

    private function export(array $entities): array
    {
        foreach ($entities as $Entity) {
            $Entity->readOne();
        }
        $Users = new Users(1, 1);
        $Make = new MakeEln($this->logger, $this->createStub(ZipStream::class), $Users, $entities, new Instance2Rors(), new Teams2Rors(1), new Users2Rors(1), includeLinkedEntities: false);
        $path = tempnam(sys_get_temp_dir(), 'elabftw-eln-groups-');
        $this->assertIsString($path);
        $Make->writeToFile($path);
        $metadataPath = basename($Make->getFileName(), '.eln') . '/ro-crate-metadata.json';
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path));
        $metadata = $archive->getFromName($metadataPath);
        $this->assertIsString($metadata);
        $archive->close();
        return array($path, $metadataPath, json_decode($metadata, true, 512, JSON_THROW_ON_ERROR));
    }

    private function replaceMetadata(string $path, string $metadataPath, array $crate): void
    {
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path));
        $this->assertTrue($archive->addFromString($metadataPath, json_encode($crate, JSON_THROW_ON_ERROR)));
        $this->assertTrue($archive->close());
    }

    private function import(string $path): array
    {
        $Users = new Users(1, 1);
        $Import = new Eln($Users, $Users, new UploadedFile($path, 'groups.eln', test: true), $this->fs, $this->logger);
        $Import->import();
        $inserted = new ReflectionProperty(Eln::class, 'insertedEntities')->getValue($Import);
        return array_map(static fn(array $entity): AbstractEntity => $entity['entity_type']->toInstance($Users, $entity['id']), $inserted);
    }

    private function content(AbstractEntity $Entity): array
    {
        $stepGroups = new StepGroups($Entity)->readAll();
        $uploadGroups = new UploadGroups($Entity)->readAll();
        $stepNames = array_column($stepGroups, 'title', 'id');
        $uploadNames = array_column($uploadGroups, 'title', 'id');
        $uploads = $Entity->Uploads->readAll(new BaseQueryParams(states: array(State::Normal, State::Archived)));
        $uploads = array_values(array_filter($uploads, static fn(array $upload): bool => $upload['real_name'] !== 'dataset-node-from-ro-crate.json'));
        return array(
            'step_groups' => array_column($stepGroups, 'title'),
            'steps' => array_map(static fn(array $step): array => array($stepNames[$step['group_id']] ?? null, $step['body'], $step['finished'], $step['ordering']), new Steps($Entity)->readAll()),
            'upload_groups' => array_column($uploadGroups, 'title'),
            'uploads' => array_map(static fn(array $upload): array => array($uploadNames[$upload['group_id']] ?? null, $upload['real_name'], $upload['state'], $upload['hash']), $uploads),
        );
    }
}
