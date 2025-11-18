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
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Enums\Action;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Hash\LocalFileHash;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Params\TagParam;
use JsonException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

use function array_find;
use function basename;
use function is_array;
use function json_decode;
use function rawurlencode;
use function sprintf;
use function strtr;

/**
 * Import a .eln file.
 */
class Eln extends AbstractZip
{
    protected const string TAGS_SEPARATOR = ',';

    // path where the metadata.json file lives (first folder found in archive)
    private string $root;

    // complete graph: all nodes from metadata json
    private array $graph;

    private array $linksToCreate = array();

    private array $insertedEntities = array();

    private array $crateNodeHasPart = array();

    private AbstractEntity $Entity;

    private int $count;

    private int $internalElnVersion = -1;

    public function __construct(
        protected Users $requester,
        // TODO nullable and have it in .eln export so it is not lost on import
        protected string $canread,
        protected string $canwrite,
        protected UploadedFile $UploadedFile,
        protected FilesystemOperator $fs,
        protected LoggerInterface $logger,
        protected ?EntityType $entityType = null,
        protected ?int $category = null,
        private bool $verifyChecksum = true,
        private bool $checksumErrorSkip = true,
    ) {
        parent::__construct(
            $requester,
            $UploadedFile,
            $fs,
        );
        $this->count = $this->preProcess();
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
    public function import(): int
    {
        // loop over each hasPart of the root node
        // this is the main import loop
        $current = 1;
        foreach ($this->crateNodeHasPart as $part) {
            $this->logger->debug(sprintf('Processing Dataset %d/%d', $current, $this->count));
            $this->importRootDataset($this->getNodeFromId($part['@id']));
            $current++;
        }

        // NOW CREATE THE LINKS
        // TODO avoid having 2 foreach loops...
        $result = array();
        foreach ($this->linksToCreate as $link) {
            foreach ($this->insertedEntities as $entity) {
                if ($link['link_@id'] === $entity['item_@id']) {
                    // grab the link node so we can get its url
                    $linkNode = $this->getNodeFromId($link['link_@id']);
                    $result[] = array(
                        'origin_entity_type' => $link['origin_entity_type'],
                        'origin_id' => $link['origin_id'],
                        'link_id' => $entity['id'],
                        'link_previous_url' => $linkNode['url'],
                        'link_entity_type' => $entity['entity_type'],
                        'link_original_id' => $link['link_@id'],
                    );
                    break;
                }
            }
        }

        foreach ($result as $linkToCreate) {
            $entity = $linkToCreate['origin_entity_type']->toInstance($this->Entity->Users, $linkToCreate['origin_id'], true, true);
            if ($linkToCreate['link_entity_type'] === EntityType::Experiments) {
                $entity->ExperimentsLinks->setId($linkToCreate['link_id']);
                $entity->ExperimentsLinks->postAction(Action::Create, array());
            } else {
                $entity->ItemsLinks->setId($linkToCreate['link_id']);
                $entity->ItemsLinks->postAction(Action::Create, array());
            }
            // now update the body with links to old id that should now point to the new id
            $linkPreviousId = $this->grabIdFromUrl($linkToCreate['link_previous_url'] ?? '');
            if ($linkPreviousId) {
                $body = preg_replace(sprintf('/(?:experiments|database)\.php\?mode=view&amp;id=(%d)/', $linkPreviousId), $linkToCreate['link_entity_type']->toPage() . '?mode=view&amp;id=' . $linkToCreate['link_id'], $entity->entityData['body'] ?? '');
                /** @psalm-suppress PossiblyInvalidCast */
                $entity->update(new EntityParams('body', (string) $body));
            }
        }
        return $this->getInserted();
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
        return $this->requester;
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

    private function grabIdFromUrl(string $url): ?int
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['query'])) {
            return null;
        }
        $queryParams = array();
        parse_str($parsedUrl['query'], $queryParams);
        if ($queryParams['id']) {
            return (int) $queryParams['id'];
        }
        return null;
    }

    private function preProcess(): int
    {
        $this->logger->debug(sprintf('temporary directory in cache: %s', $this->tmpDir));
        $this->root = $this->getRootDirectory();
        $this->graph = $this->getGraph();

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

    // figure out the path to the root of the eln (where the metadata file lives)
    // folder name is variable, so list folders and pick the first one found (there should be only one)
    private function getRootDirectory(): string
    {
        $listing = $this->tmpFs->listContents($this->tmpDir);
        foreach ($listing as $item) {
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $this->logger->debug(sprintf('Found root directory in archive: %s', basename($item->path())));
                return $item->path();
            }
        }
        throw new ImproperActionException('Could not find a directory in the ELN archive!');
    }

    private function getGraph(): array
    {
        $metadataFile = 'ro-crate-metadata.json';
        try {
            $content = $this->tmpFs->read($this->root . '/' . $metadataFile);
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (UnableToReadFile) {
            throw new ImproperActionException(sprintf(_('Error: could not read archive file properly! (missing %s)'), $metadataFile));
        } catch (JsonException $e) {
            throw new ImproperActionException($e->getMessage());
        }
        return $json['@graph'];
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
        $this->Entity->setId($this->Entity->create());

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
                    $this->Entity->update(new EntityParams('category', (string) $categoryId));
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
                        $this->Entity->Comments->postAction(Action::Create, array('comment' => $content));
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
                                break;
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
                            $this->Entity->Steps->importFromHowToStepOld($step);
                        }
                    } else {
                        foreach ($value as $id) {
                            $step = $this->getNodeFromId($id['@id']);
                            $body = $this->getNodeFromId($step['itemListElement']['@id'])['text'];
                            $this->Entity->Steps->importFromHowToStep($step, $body);
                        }
                    }
                    break;
                    // TAGS: should normally be a comma separated string, but we allow array for BC
                case 'keywords':
                    $tags = $value;
                    if (is_string($tags)) {
                        $tags = explode(self::TAGS_SEPARATOR, $tags);
                    }
                    foreach ($tags as $tag) {
                        if (!empty($tag)) {
                            $this->Entity->Tags->create(new TagParam($this->transformIfNecessary($tag)), true);
                        }
                    }
                    break;

                default:
            }
        }

        // do the CUSTOM ID after everything (especially after the category) so we can catch any error when setting it and we also have a chance to set the category before the custom_id is set
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
                return;
        }
    }

    private function importFile(array $file): void
    {
        // note: path transversal vuln is detected and handled by flysystem
        $filepath = $this->tmpPath . '/' . basename($this->root) . '/' . $file['@id'];
        // fix for bloxberg attachments containing : character
        $filepath = strtr($filepath, ':', '_');
        // quick patch to fix issue with | in the title, but we will need a proper fix to avoid the need for such patches...
        $filepath = strtr($filepath, '|', '_');
        $filepath = strtr($filepath, '"', '_');

        $hasher = new LocalFileHash($filepath);
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
        $newUploadId = $this->Entity->Uploads->create(new CreateUpload(
            $file['name'] ?? basename($file['@id']),
            $filepath,
            $hasher,
            $this->transformIfNecessary($file['description'] ?? '', true) ?: null,
            state: ($file['creativeWorkStatus'] ?? '') === State::Archived->name ? State::Archived : State::Normal
        ));
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
