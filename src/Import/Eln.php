<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use DateTimeImmutable;
use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\CreateUploadFromFs;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Hash\FileHash;
use Elabftw\Interfaces\CompoundInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Comments;
use Elabftw\Models\StepGroups;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\Changelog;
use Elabftw\Models\UploadGroups;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users\Users;
use Elabftw\Params\ContentParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\TagParam;
use Elabftw\Services\Filter;
use JsonException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;
use ZipArchive;

use function array_find;
use function array_is_list;
use function array_values;
use function basename;
use function is_array;
use function json_decode;
use function min;
use function rawurlencode;
use function sprintf;
use function strtr;
use function _;
use function array_key_exists;
use function count;
use function date;
use function explode;
use function filter_var;
use function in_array;
use function is_string;
use function is_numeric;
use function json_encode;
use function parse_str;
use function parse_url;
use function str_replace;
use function str_starts_with;
use function ucfirst;
use function usort;

/**
 * Import a .eln file.
 */
class Eln extends AbstractZip
{
    protected const string TAGS_SEPARATOR = ',';

    // archive directory containing ro-crate-metadata.json
    private string $root;

    // complete graph: all nodes from metadata json
    private array $graph;

    private array $linksToCreate = array();

    private array $metadataReferencesToResolve = array();

    private array $insertedEntities = array();

    private array $insertedCompounds = array();

    private array $entityStatesToRestore = array();

    private array $crateNodeHasPart = array();

    private AbstractEntity $Entity;

    private int $count;

    private int $internalElnVersion = -1;

    // RO-Crate IDs are mapped to freshly created groups for the current entity only.
    private array $groupIds = array();

    private array $groupMembers = array();

    private array $importedUploads = array();

    /** @var list<array{changelog: Changelog, changes: array}> */
    private array $changelogsToRestore = array();

    public function __construct(
        protected Users $requester,
        protected Users $targetUser,
        protected UploadedFile $UploadedFile,
        protected FilesystemOperator $fs,
        protected LoggerInterface $logger,
        protected CompoundInterface $Compounds,
        protected ?EntityType $entityType = null,
        protected ?int $category = null,
        protected BasePermissions $canreadBase = BasePermissions::Team,
        protected BasePermissions $canwriteBase = BasePermissions::User,
        // TODO nullable and have it in .eln export so it is not lost on import
        protected string $canread = AbstractEntity::EMPTY_CAN_JSON,
        protected string $canwrite = AbstractEntity::EMPTY_CAN_JSON,
        private bool $verifyChecksum = true,
        private bool $checksumErrorSkip = false,
    ) {
        parent::__construct(
            $requester,
            $UploadedFile,
            $fs,
            $logger,
        );
        // we might have been forced to cast to int a null value, so bring it back to null
        if ($this->category === 0) {
            $this->category = null;
        }
    }

    #[Override]
    public function getCount(): int
    {
        return $this->count;
    }

    #[Override]
    public function getTargetUserid(): int
    {
        return $this->targetUser->getUserid();
    }

    #[Override]
    public function import(): int
    {
        try {
            $this->prepare();
            return $this->importDatasets();
        } finally {
            $this->cleanup();
        }
    }

    protected function getEntityType(array $dataset): EntityType
    {
        // if it is present in the object, it means we force the entityType
        if ($this->entityType !== null) {
            return $this->entityType;
        }
        // otherwise try looking into "genre" attribute
        if (!empty($dataset['genre'])) {
            return match ($dataset['genre']) {
                'experiment', 'experiments' => EntityType::Experiments,
                'experiment template', 'protocol', 'protocols', 'template' => EntityType::Templates,
                'resource template' => EntityType::ItemsTypes,
                // everything else is a Resource
                default => EntityType::Items,
            };
        }
        // not sure if best to throw an Exception here or have a default
        $this->logger->notice('Could not find entity type (genre), falling back to Resource');
        return EntityType::Items;

    }

    #[Override]
    protected function readArchiveMetadata(ZipArchive $archive): void
    {
        $metadataFile = 'ro-crate-metadata.json';
        $metadataIndex = null;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index, ZipArchive::FL_UNCHANGED);
            if ($name === false) {
                continue;
            }
            $path = str_replace('//', '/', $name);
            $parts = explode('/', $path);
            if (count($parts) === 2 && $parts[1] === $metadataFile) {
                if ($metadataIndex !== null) {
                    throw new ImproperActionException('The ELN archive contains more than one metadata file.');
                }
                $metadataIndex = $index;
                $this->root = $parts[0];
            }
        }
        if ($metadataIndex === null) {
            throw new ImproperActionException(sprintf(_('Error: could not read archive file properly! (missing %s)'), $metadataFile));
        }
        $input = $archive->getStreamIndex($metadataIndex, ZipArchive::FL_UNCHANGED);
        if ($input === false) {
            throw new ImproperActionException('Could not read the ELN metadata.');
        }
        $limited = new LimitedReadStream(
            $input,
            $this->enforceLimits ? min($this->limits->maxMetadataBytes, $this->limits->maxMemberBytes, $this->limits->maxTotalBytes) : null,
        );
        try {
            $json = json_decode($limited->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImproperActionException($e->getMessage());
        } finally {
            $limited->close();
        }
        if (!is_array($json) || !isset($json['@graph']) || !is_array($json['@graph']) || !array_is_list($json['@graph'])) {
            throw new ImproperActionException('The ELN metadata must contain an @graph array.');
        }
        foreach ($json['@graph'] as $node) {
            if (!is_array($node) || !isset($node['@id']) || !is_string($node['@id'])) {
                throw new ImproperActionException('The ELN metadata contains an invalid graph node.');
            }
        }
        $this->graph = $json['@graph'];
        $this->count = $this->preProcess();
    }

    protected function getNodeFromId(string $id): array
    {
        $result = array_find(
            $this->graph,
            function (array $node) use ($id) {
                return $node['@id'] === $id;
            }
        );
        // looking up a node by id should always return something, otherwise there is something seriously wrong
        if ($result === null) {
            $this->logger->error(sprintf('Error looking up the node id %s', $id));
            return array();
        }
        return $result;
    }

    protected function getAuthor(array $dataset): Users
    {
        return $this->targetUser;
    }

    protected function getChangelogAuthor(array $action): Users
    {
        return $this->requester;
    }

    protected function getMetadataUserReference(string $personId): int
    {
        return $this->targetUser->getUserid();
    }

    private function importDatasets(): int
    {
        // loop over each hasPart of the root node
        // this is the main import loop
        $current = 1;
        foreach ($this->crateNodeHasPart as $part) {
            $this->logger->debug(sprintf('Processing Dataset %d/%d', $current, $this->count));
            $this->importRootDataset($this->getNodeFromId($part['@id']));
            $current++;
        }

        $insertedEntitiesIds = array();
        foreach ($this->insertedEntities as $inserted) {
            $insertedEntitiesIds[$inserted['item_@id']] = $inserted['id'];
        }
        foreach ($this->metadataReferencesToResolve as $ref) {
            $targetIds = array();
            $resolved = true;
            foreach ($ref['target_@ids'] as $target) {
                $targetId = match ($ref['target_type']) {
                    'users' => $this->getMetadataUserReference($target),
                    'compounds' => $this->getOrCreateCompound($target),
                    default => $insertedEntitiesIds[$target] ?? null,
                };
                if ($targetId === null) {
                    $this->logger->warning(sprintf('Could not resolve metadata reference %s.', $target));
                    $resolved = false;
                    break;
                }
                $targetIds[] = $targetId;
            }
            $entity = $ref['origin_entity_type']->toInstance(
                $this->targetUser,
                $ref['origin_id'],
                true,
                true,
            );
            if (!$resolved || $targetIds === array()) {
                $entity->patch(Action::UpdateMetadataField, array(
                    $ref['property_id'] => array(
                        'value' => $ref['is_multiple'] ? array() : '',
                        'value_labels' => null,
                    ),
                ));
                continue;
            }
            $entity->patch(
                Action::UpdateMetadataField,
                array($ref['property_id'] => $ref['is_multiple'] ? $targetIds : $targetIds[0])
            );
        }

        // All imported entities now have their target IDs
        $linkReplacements = $this->getChangelogLinkReplacements();

        // Rewrite each body once, before restoring its original history.
        foreach ($this->insertedEntities as $inserted) {
            $entity = $inserted['entity_type']->toInstance(
                $this->Entity->Users,
                $inserted['id'],
                true,
                true,
            );

            $oldBody = $entity->entityData['body'] ?? '';
            $body = strtr($oldBody, $linkReplacements);

            if ($body !== $oldBody) {
                $entity->update(new EntityParams('body', $body));
            }
        }
        // Restore the original changelog before creating links
        foreach ($this->changelogsToRestore as $pending) {
            $changes = $pending['changes'];

            foreach ($changes as $index => $change) {
                if (in_array($change['target'], array('links', 'created', 'body', 'bodyappend'), true)) {
                    $changes[$index]['content'] = strtr($change['content'], $linkReplacements);
                }
            }
            $pending['changelog']->replaceAll($changes);
        }
        // NOW CREATE THE LINKS
        // TODO avoid having 2 foreach loops...
        $result = array();
        foreach ($this->linksToCreate as $link) {
            foreach ($this->insertedEntities as $entity) {
                if ($link['link_@id'] === $entity['item_@id']) {
                    $result[] = array(
                        'origin_entity_type' => $link['origin_entity_type'],
                        'origin_id' => $link['origin_id'],
                        'link_id' => $entity['id'],
                        'link_entity_type' => $entity['entity_type'],
                    );
                    break;
                }
            }
        }

        foreach ($result as $linkToCreate) {
            $entity = $linkToCreate['origin_entity_type']->toInstance($this->Entity->Users, $linkToCreate['origin_id'], true, true);
            if ($linkToCreate['link_entity_type'] === EntityType::Experiments) {
                $entity->ExperimentsLinks->setId($linkToCreate['link_id']);
                $entity->ExperimentsLinks->createForImport();
            } else {
                $entity->ItemsLinks->setId($linkToCreate['link_id']);
                $entity->ItemsLinks->createForImport();
            }
        }

        // Restore entity state (normal, archived, deleted).
        $this->restoreEntityStates();

        return $this->getInserted();
    }

    // Retrieve compound id from graph JSON-LD instead of sql
    private function getOrCreateCompound(string $id): ?int
    {
        if (isset($this->insertedCompounds[$id])) {
            return $this->insertedCompounds[$id];
        }

        $node = $this->getNodeFromId($id);
        if (($node['@type'] ?? null) !== 'MolecularEntity') {
            return null;
        }
        $compoundId = $this->Compounds->createFromCompound($this->compoundFromJsonLd($node));
        $this->insertedCompounds[$id] = $compoundId;
        return $compoundId;
    }

    private function compoundFromJsonLd(array $node): Compound
    {
        $casNumber = null;
        $pubchemCid = null;

        foreach ($node['identifier'] ?? array() as $identifier) {
            if (!is_array($identifier)) {
                continue;
            }
            if (($identifier['propertyID'] ?? null) === 'CAS Registry Number') {
                $casNumber = $identifier['value'] ?? null;
            }
            if (($identifier['propertyID'] ?? null) === 'PubChem CID') {
                $value = $identifier['value'] ?? null;
                if (is_numeric($value) && (int) $value > 0) {
                    $pubchemCid = (int) $value;
                }
            }
        }
        return new Compound(
            cas: $casNumber,
            cid: $pubchemCid,
            inChI: $node['inChI'] ?? null,
            inChIKey: $node['inChIKey'] ?? null,
            name: $node['name'] ?? null,
            iupacName: $node['iupacName'] ?? null,
            smiles: $node['smiles'] ?? null,
            molecularFormula: $node['molecularFormula'] ?? null,
            molecularWeight: isset($node['molecularWeight']) ? (float) $node['molecularWeight'] : null,
        );
    }

    private function getChangelogLinkReplacements(): array
    {
        $replacements = array();
        foreach ($this->insertedEntities as $entity) {
            $dataset = $this->getNodeFromId($entity['item_@id']);
            $url = $dataset['url'] ?? null;
            if (!is_string($url)) {
                continue;
            }
            $source = parse_url($url);
            if (!isset($source['path'], $source['query'])) {
                continue;
            }

            $query = array();
            parse_str($source['query'], $query);
            $sourceId = filter_var($query['id'] ?? null, FILTER_VALIDATE_INT);
            if ($sourceId === false) {
                continue;
            }
            $sourcePage = basename($source['path']);
            $sourceType = match ($sourcePage) {
                'experiments.php' => EntityType::Experiments,
                'database.php' => EntityType::Items,
                'templates.php' => EntityType::Templates,
                'resources-templates.php' => EntityType::ItemsTypes,
                default => null,
            };
            if ($sourceType === null) {
                continue;
            }
            $targetPage = $entity['entity_type']->toPage();
            $targetType = $entity['entity_type']->value;
            $targetId = $entity['id'];

            // Entity links
            $replacements[sprintf('%s?mode=view&amp;id=%d', $sourcePage, $sourceId)] = sprintf('%s?mode=view&amp;id=%d', $targetPage, $targetId);
            // Entity revisions
            $replacements[sprintf('revisions.php?type=%s&amp;item_id=%d', $sourceType->value, $sourceId)] = sprintf('revisions.php?type=%s&amp;item_id=%d', $targetType, $targetId);
            // URL in the body.
            $replacements[sprintf('%s?id=%d&amp;mode=view', $sourcePage, $sourceId)] = sprintf('%s?mode=view&amp;id=%d', $targetPage, $targetId);
        }
        return $replacements;
    }

    private function preProcess(): int
    {
        foreach ($this->graph as $node) {
            // find the node describing the crate
            if ($node['@id'] === './') {
                $this->crateNodeHasPart = $node['hasPart'] ?? array();
                if (array_key_exists('version', $node)) {
                    $this->internalElnVersion = (int) $node['version'];
                }
            }
            // detect old elabftw (<5.0.0-beta2) versions where we need to decode characters
            // only newer versions have the areaServed attribute
            if ($node['@id'] === 'ro-crate-metadata.json' &&
                array_key_exists('sdPublisher', $node)) {
                if (!array_key_exists('@id', $node['sdPublisher'])) {
                    continue;
                }

                $sdPublisher = $this->getNodeFromId($node['sdPublisher']['@id']);
                if (!array_key_exists('areaServed', $sdPublisher)) {
                    $this->logger->debug('Found old eLabFTW signature: HTML entities will be converted');
                    $this->switchToEscapeOutput = true;
                }
            }
        }
        return count($this->crateNodeHasPart);
    }

    /**
     * Import a node of `@type` Dataset: the main kind of node that corresponds to an entity
     */
    private function importRootDataset(array $dataset): void
    {
        if (($dataset['@type'] ?? null) !== 'Dataset') {
            $this->logger->debug(sprintf('Skipping import of non-dataset %s', $dataset['@id'] ?? ''));
            return;
        }
        $Author = $this->getAuthor($dataset);

        // a .eln can contain mixed types: experiments, resources, or templates.
        $entityType = $this->getEntityType($dataset);

        $this->Entity = $entityType->toInstance($Author, bypassReadPermission: true, bypassWritePermission: true);

        // CATEGORY
        $categoryId = $this->category;
        if (isset($dataset['about']) && $this->category === null) {
            $categoryNode = $this->getNodeFromId($dataset['about']['@id']);
            $categoryId = $this->getCategoryId($entityType, $categoryNode['name'], $categoryNode['color']);
        }

        // CREATE ENTITY
        $entityId = $this->Entity->create();
        $this->Entity->setId($entityId);
        $this->logger->debug(sprintf('Created %s with id: %d', $this->Entity->entityType->value, $entityId));
        // Groups must exist before steps or files are imported, regardless of JSON property order.
        $this->importGroups($dataset);

        // DATE
        $date = date('Y-m-d');
        if (isset($dataset['temporal'])) {
            $date = (new DateTimeImmutable($dataset['temporal']))->format('Y-m-d');
        }
        $this->Entity->update(new EntityParams('date', $date));

        // keep a reference between the `@id` and the fresh id to resolve links later
        $this->insertedEntities[] = array('item_@id' => $dataset['@id'], 'id' => $this->Entity->id, 'entity_type' => $this->Entity->entityType);
        // fix issue with immutable permissions
        $this->Entity->entityData['canread_is_immutable'] = 0;
        $this->Entity->entityData['canwrite_is_immutable'] = 0;
        // canread and canwrite patch must happen before bodyappend that contains a readOne()
        $this->Entity->update(new EntityParams('canread_base', $this->canreadBase->value));
        $this->Entity->update(new EntityParams('canwrite_base', $this->canwriteBase->value));
        $this->Entity->update(new EntityParams('canread', $this->canread));
        $this->Entity->update(new EntityParams('canwrite', $this->canwrite));
        // content_type
        $contentType = ($dataset['encodingFormat'] ?? 'text/html') === 'text/markdown' ? BodyContentType::Markdown : BodyContentType::Html;
        $this->Entity->update(new EntityParams('content_type', $contentType->value));
        // here we use "text" or "description" attribute as main text
        $this->Entity->update(new EntityParams('bodyappend', ($dataset['text'] ?? '') . ($dataset['description'] ?? '')));
        // TITLE
        $title = $this->transformIfNecessary($dataset['name'] ?? _('Untitled'));
        $this->Entity->update(new EntityParams('title', $title));

        // now we import all the remaining attributes as text/links in the main text
        // we still have an allowlist of attributes imported, which also allows to switch between the kind of values expected
        $bodyAppend = '';
        foreach ($dataset as $attributeName => $value) {
            switch ($attributeName) {
                // CATEGORY
                case 'about':
                    $this->Entity->update(new EntityParams('category', $categoryId));
                    break;
                    // COMMENTS
                case 'comment':
                    foreach ($value as $comment) {
                        // for backward compatibility with elabftw's .eln from before 4.9, the "comment" attribute MAY contain all, instead of just being a link with an @id
                        // after 4.9 the "comment" attribute contains only a link to an @type: Comment node
                        if (count($comment) === 1) {
                            // resolve the id to get the full node content
                            $comment = $this->getNodeFromId($comment['@id']);
                        }
                        $author = $this->getNodeFromId($comment['author']['@id']);
                        $content = sprintf(
                            "Imported comment from %s %s (%s)\n\n%s",
                            $this->transformIfNecessary($author['givenName'] ?? ''),
                            $this->transformIfNecessary($author['familyName'] ?? '') ?: $author['name'] ?? 'Unknown',
                            $comment['dateCreated'],
                            $this->transformIfNecessary($comment['text'] ?? '', true),
                        );
                        new Comments($this->Entity)->postAction(Action::Create, array('comment' => $content));
                    }
                    break;

                case 'citation':
                case 'license':
                    $bodyAppend .= sprintf('<h1>%s</h1><ul><li><a href="%s">%s</a></li></ul>', _(ucfirst($attributeName)), $value['@id'], $value['@id']);
                    break;
                case 'funder':
                    $bodyAppend .= $this->attrToHtml($value, _(ucfirst($attributeName)));
                    break;
                    // LINKS
                case 'mentions':
                    foreach ($value as $mention) {
                        // for backward compatibility with elabftw's .eln from before 4.9, the "mention" attribute MAY contain all, instead of just being a link with an @id
                        // after 4.9 the "mention" attribute contains only a link to an @type: Dataset node
                        // after 5.1 the "mention" will point to a Dataset contained in the .eln
                        if (is_array($mention) && count($mention) === 1) {
                            // store a reference for the link to create. We cannot create it now as link might or might not exist yet.
                            $this->linksToCreate[] = array(
                                'origin_entity_type' => $this->Entity->entityType,
                                'origin_id' => $this->Entity->id,
                                'link_@id' => $mention['@id'],
                            );
                        }
                    }
                    break;

                    // METADATA
                case 'variableMeasured':
                    foreach ($value ?? array() as $propval) {
                        // versions before 103 will not be flattened and hold an array of pv
                        // (in 103 we have an array of id)
                        // INTERNAL_ELN_VERSION < 103
                        if ($this->internalElnVersion < 103) {
                            // crates from other tools have no version but are flattened: resolve a bare {"@id": ...} to its PropertyValue node
                            if (!array_key_exists('propertyID', $propval) && array_key_exists('@id', $propval)) {
                                $propval = $this->getNodeFromId($propval['@id']);
                            }
                            if (array_key_exists('propertyID', $propval) && $propval['propertyID'] === 'elabftw_metadata') {
                                // we look for the special elabftw_metadata property and that's what we import
                                $this->Entity->update(new EntityParams('metadata', $propval['value']));
                                break;
                            }
                        } else {
                            // INTERNAL_ELN_VERSION >= 103
                            $node = $this->getNodeFromId($propval['@id']);
                            if ($node['propertyID'] === 'elabftw_metadata') {
                                $this->Entity->update(new EntityParams('metadata', $node['value']));
                                continue;
                            }
                            $type = $node['valueReference'] ?? '';
                            $value = $node['value'] ?? null;
                            if (in_array($type, array('compounds', 'experiments', 'items', 'users'), true) && is_array($value)) {
                                $isMultiple = array_is_list($value);
                                $referenceValues = $isMultiple ? $value : array($value);
                                $targetAtIds = array();
                                foreach ($referenceValues as $referenceValue) {
                                    if (!is_array($referenceValue)) {
                                        continue;
                                    }
                                    $targetAtId = $referenceValue['@id'] ?? null;
                                    if (is_string($targetAtId)) {
                                        $targetAtIds[] = $targetAtId;
                                    }
                                }
                                if ($targetAtIds !== array()) {
                                    $this->metadataReferencesToResolve[] = array(
                                        'origin_entity_type' => $this->Entity->entityType,
                                        'origin_id' => $this->Entity->id,
                                        'property_id' => $node['propertyID'],
                                        'target_@ids' => $targetAtIds,
                                        'target_type' => $type,
                                        'is_multiple' => $isMultiple,
                                    );
                                }
                            }
                        }
                    }
                    break;

                    // RATING
                case 'aggregateRating':
                    $this->Entity->update(new EntityParams('rating', (string) ($value['ratingValue'] ?? '0')));
                    break;
                    // STATUS
                case 'creativeWorkStatus':
                    $this->Entity->update(new EntityParams('status', (string) $this->getStatusId($entityType, $value)));
                    break;
                    // STEPS
                case 'step':
                    if ($this->internalElnVersion < 104) {
                        foreach ($value as $step) {
                            new Steps($this->Entity)->importFromHowToStepOld($step);
                        }
                    } else {
                        foreach ($value as $id) {
                            $step = $this->getNodeFromId($id['@id']);
                            $body = $this->getNodeFromId($step['itemListElement']['@id'])['text'];
                            new Steps($this->Entity)->importFromHowToStep($step, $body, $this->getImportedGroupId($step, 'steps'));
                        }
                    }
                    break;
                    // TAGS: should normally be a comma separated string, but we allow array for BC
                case 'keywords':
                    $tags = $value;
                    if (is_string($tags)) {
                        $tags = explode(self::TAGS_SEPARATOR, $tags);
                    }
                    $Tags = new Tags($this->Entity);
                    foreach ($tags as $tag) {
                        if (!empty($tag)) {
                            $Tags->create(new TagParam($this->transformIfNecessary($tag)), true);
                        }
                    }
                    break;
                case 'compounds_links':
                    foreach ($value as $compoundLink) {
                        $id = $compoundLink['@id'] ?? null;
                        if (!is_string($id)) {
                            continue;
                        }
                        $compoundId = $this->getOrCreateCompound($id);
                        if ($compoundId === null) {
                            $this->logger->warning(sprintf('Could not resolve compound %s during ELN import.', $id));
                            continue;
                        }
                        $CompoundsLinks = LinksFactory::getCompoundsLinks($this->Entity, $compoundId);
                        $CompoundsLinks->postAction(Action::Create, array());
                    }
                    break;
                default:
            }
        }

        // Make sure an explicitly requested import category is applied before
        // custom_id so uniqueness is checked against the final category.
        if ($this->category !== null) {
            $this->Entity->update(new EntityParams('category', $this->category));
        }

        // do the CUSTOM ID after everything (especially after the category)
        if (array_key_exists('alternateName', $dataset)) {
            try {
                $this->Entity->patch(Action::Update, array('custom_id' => (string) $dataset['alternateName']));
                // just log the error, don't try and set another custom_id
            } catch (ImproperActionException) {
                $this->logger->error(
                    sprintf('Could not add custom_id to entity %s:%d as it is already in use', $this->Entity->entityType->value, (int) $this->Entity->id)
                );
            }
        }

        $this->Entity->patch(Action::Update, array('bodyappend' => $bodyAppend));

        // also save the Dataset node as a .json file so we don't lose information with things not imported
        // saved as an archived file, so it doesn't appear in the UI but is still there if needed
        $this->Entity->Uploads->createFromString(
            FileFromString::Json,
            'dataset-node-from-ro-crate.json',
            json_encode($dataset, JSON_THROW_ON_ERROR, 1024),
            State::Archived,
        );

        $this->inserted++;
        // now loop over the parts of this node to find the rest of the files
        // the getNodeFromId might return nothing but that's okay, we just continue to try and find stuff
        foreach ($dataset['hasPart'] ?? array() as $part) {
            $this->importPart($this->getNodeFromId($part['@id']));
        }
        $this->restoreUploadOrdering();

        $this->entityStatesToRestore[] = array(
            'entity_type' => $this->Entity->entityType,
            'entity_id' => $this->Entity->id,
            'dataset' => $dataset,
        );

        // Prepare the original changelog; restore it once all imported entity IDs are known
        if (!empty($dataset['subjectOf'])) {
            $this->changelogsToRestore[] = array(
                'changelog' => new Changelog($this->Entity),
                'changes' => $this->updateActionsToChangelog($dataset['subjectOf']),
            );
        }
    }

    private function restoreEntityStates(): void
    {
        foreach ($this->entityStatesToRestore as $pending) {
            $entity = $pending['entity_type']->toInstance(
                $this->targetUser,
                $pending['entity_id'],
                true,
                true,
            );
            $dataset = $pending['dataset'];
            $state = State::fromName($dataset['status'] ?? State::Normal->name);
            // we use match(true) to be able to match a State and the Locked pseudo-state in the same match
            match (true) {
                $state === State::Archived => $entity->patch(Action::Archive, array()),
                $state === State::Deleted => $entity->patch(Action::Update, array('state' => $state->value)),
                // Restore locked/unlocked condition.
                ($dataset['conditionsOfAccess'] ?? '') === 'Locked' => $this->restoreLock($entity, $dataset),
                default => null,
            };
        }
    }

    private function restoreLock(AbstractEntity $entity, array $dataset): array
    {
        $result = $entity->patch(Action::Lock, array());
        $lockedAt = null;
        foreach ($dataset['subjectOf'] ?? array() as $action) {
            if (($action['object'] ?? '') !== 'locked'
               || ($action['result'] ?? '') !== 'Locked'
               || !is_string($action['startTime'] ?? null)
            ) {
                continue;
            }
            $startTime = new DateTimeImmutable($action['startTime']);
            if ($lockedAt === null || $startTime > $lockedAt) {
                $lockedAt = $startTime;
            }
        }
        if ($lockedAt !== null) {
            $entity->update(new ContentParams('locked_at', date('Y-m-d H:i:s', $lockedAt->getTimestamp())));
        }
        return $result;
    }

    /**
     * Recreate named groups, including empty groups, before importing their members.
     */
    private function importGroups(array $dataset): void
    {
        $this->groupIds = array('steps' => array(), 'uploads' => array());
        $this->groupMembers = array('steps' => array(), 'uploads' => array());
        $this->importedUploads = array();
        $groups = array();
        foreach ($dataset['hasPart'] ?? array() as $part) {
            $group = $this->getNodeFromId($part['@id']);
            if (!in_array($group['@type'] ?? '', array('HowToSection', 'Collection'), true)) {
                continue;
            }
            // A group belonging to another dataset must not be reused for this entity.
            if (($group['isPartOf']['@id'] ?? $dataset['@id']) !== $dataset['@id']) {
                continue;
            }
            $groups[$group['@id']] = $group;
        }
        $groups = array_values($groups);
        usort($groups, static fn(array $a, array $b): int => (int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0));
        foreach ($groups as $group) {
            $isStepGroup = $group['@type'] === 'HowToSection';
            $kind = $isStepGroup ? 'steps' : 'uploads';
            $Groups = $isStepGroup ? new StepGroups($this->Entity) : new UploadGroups($this->Entity);
            $id = $Groups->postAction(Action::Create, array('title' => $this->transformIfNecessary($group['name'] ?? _('Untitled'))));
            $this->groupIds[$kind][$group['@id']] = $id;
            foreach ($group[$isStepGroup ? 'itemListElement' : 'hasPart'] ?? array() as $member) {
                $this->groupMembers[$kind][$member['@id']] = $id;
            }
        }
    }

    private function getImportedGroupId(array $node, string $kind): ?int
    {
        return $this->groupIds[$kind][$node['isPartOf']['@id'] ?? '']
            ?? $this->groupMembers[$kind][$node['@id']]
            ?? null;
    }

    private function restoreUploadOrdering(): void
    {
        if ($this->groupIds['uploads'] === array() || $this->importedUploads === array()) {
            return;
        }
        $uploads = array_values($this->importedUploads);
        usort($uploads, static fn(array $a, array $b): int => $a['position'] <=> $b['position']);
        $ordering = array();
        foreach ($uploads as $upload) {
            $key = $upload['group_id'] ?? 'default';
            $ordering[$key] ??= array('group_id' => $upload['group_id'], 'upload_ids' => array());
            $ordering[$key]['upload_ids'][] = $upload['id'];
        }
        $this->Entity->Uploads->patch(Action::Update, array('grouped_ordering' => array_values($ordering)));
    }

    // convert export UpdateAction to changelog dataset
    private function updateActionsToChangelog(array $actions): array
    {
        $changelog = array();
        foreach ($actions as $action) {
            if (($action['@type'] ?? '') !== 'UpdateAction') {
                continue;
            }
            $target = is_string($action['object'] ?? null) ? $action['object'] : 'import';
            $content = is_string($action['result'] ?? null) ? $action['result'] : '';
            $Author = $this->getChangelogAuthor($action);

            // These targets are rendered as raw HTML in changelog-table.html. Imported
            // history is untrusted and must pass through the same filter as entity bodies.
            if (in_array($target, array('body', 'links', 'created'), true)) {
                $content = Filter::body($content);
            }
            $changelog[] = array(
                'created_at' => new DateTimeImmutable($action['startTime'])->format('Y-m-d H:i:s'),
                'target' => $target,
                'content' => $content,
                'userid' => $Author->getUserid(),
            );
        }
        return $changelog;
    }

    private function attrToHtml(array $attr, string $title): string
    {
        $html = sprintf('<h1>%s</h1><ul>', $title);
        foreach ($attr as $elem) {
            if (is_string($elem)) {
                $html .= sprintf('<li><a href="%s">%s</a></li>', $elem, $elem);
                continue;
            }
            $node = $this->getNodeFromId($elem['@id']);
            $html .= sprintf('<li><a href="%s">%s</a></li>', $node['@id'], $node['name']);
        }
        return $html . '</ul>';
    }

    private function importPart(array $part): void
    {
        if (!array_key_exists('@type', $part) || empty($part['@type'])) {
            return;
        }

        switch ($part['@type']) {
            case 'Collection':
                if (!isset($this->groupIds['uploads'][$part['@id']])) {
                    break;
                }
                // Files can be referenced by both the entity and the collection. importFile deduplicates them.
                foreach ($part['hasPart'] ?? array() as $reference) {
                    $file = $this->getNodeFromId($reference['@id']);
                    if (($file['@type'] ?? '') === 'File') {
                        $this->importPart($file);
                    }
                }
                break;
            case 'Dataset':
                $this->Entity->patch(Action::Update, array('bodyappend' => $this->part2html($part)));
                foreach ($part['hasPart'] ?? array() as $subpart) {
                    if (($subpart['@type'] ?? '') === 'File') {
                        $this->importFile($subpart);
                    }
                }
                break;
            case 'File':
                if (str_starts_with($part['@id'], 'http')) {
                    // we don't import remote files
                    return;
                }
                $this->importFile($part);
                break;
            default:
        }
    }

    private function importFile(array $file): void
    {
        $id = (string) $file['@id'];
        try {
            $this->assertArchivePathIsSafe($id);
        } catch (ImproperActionException) {
            $this->logger->error(sprintf('Skipping file with unsafe @id: %s', $id));
            return;
        }
        if (isset($this->importedUploads[$id])) {
            return;
        }
        $filepath = $this->tmpDir . '/' . basename($this->root) . '/' . $id;
        $this->logger->debug(sprintf('Importing file: %s', $filepath));
        // fix for bloxberg attachments containing : character
        $filepath = strtr($filepath, ':', '_');
        // quick patch to fix issue with | in the title, but we will need a proper fix to avoid the need for such patches...
        $filepath = strtr($filepath, '|', '_');
        $filepath = strtr($filepath, '"', '_');

        $hasher = new FileHash($this->tmpFs, $filepath);
        $hash = $hasher->getHash();
        // CHECKSUM
        if ($this->verifyChecksum && $hash !== $file['sha256']) {
            $this->logger->error(sprintf(
                'Error: %s has incorrect sha256 sum. Expected: %s. Actual: %s',
                basename($filepath),
                $file['sha256'],
                $hash ?? '?',
            ));
            if ($this->checksumErrorSkip) {
                $this->logger->error('File was not imported.');
                return;
            }
        }
        // CREATE
        $groupId = $this->getImportedGroupId($file, 'uploads');
        $newUploadId = $this->Entity->Uploads->create(new CreateUploadFromFs(
            $this->tmpFs,
            $file['name'] ?? basename($file['@id']),
            $filepath,
            $this->transformIfNecessary($file['description'] ?? '', true) ?: null,
            state: ($file['creativeWorkStatus'] ?? '') === State::Archived->name ? State::Archived : State::Normal,
        ), groupId: $groupId);
        $this->importedUploads[$file['@id']] = array(
            'id' => $newUploadId,
            'group_id' => $groupId,
            'position' => (int) ($file['position'] ?? count($this->importedUploads)),
        );
        // the alternateName holds the previous long_name of the file
        if (!empty($file['alternateName'])) {
            // read the newly created upload so we can get the new long_name to replace the old in the body
            $Uploads = new Uploads($this->Entity, $newUploadId);
            $currentBody = $this->Entity->readOne()['body'];
            // also search for url encoded filename
            $newBody = str_replace(array(rawurlencode($file['alternateName']), $file['alternateName']), $Uploads->uploadData['long_name'], $currentBody);
            $this->Entity->patch(Action::Update, array('body' => $newBody));
        }
    }

    private function part2html(array $part): string
    {
        $html = sprintf('<p>%s<br>%s', $part['name'] ?? '', $part['dateCreated'] ?? '');
        $html .= '<ul>';
        foreach ($part['hasPart'] ?? array() as $subpart) {
            $html .= sprintf(
                '<li>%s %s</li>',
                basename($subpart['@id']),
                $this->transformIfNecessary($subpart['description'] ?? ''),
            );
        }
        $html .= '</ul>';
        return $html;
    }
}
