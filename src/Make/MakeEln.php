<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Metadata;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\TwigTrait;
use League\Flysystem\UnableToReadFile;
use ZipStream\ZipStream;
use Override;

use function array_push;
use function mb_substr;
use function ksort;

/**
 * Make an ELN archive
 */
class MakeEln extends AbstractMakeEln
{
    use TwigTrait;

    public function __construct(protected ZipStream $Zip, protected Users $requester, protected array $entityArr)
    {
        parent::__construct($Zip);
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    #[Override]
    public function getStreamZip(): void
    {
        $this->processEntityArr();

        $rootNode = $this->getRootNode();

        // make a copy because we don't want to append to the instance property variable as it's used in html preview
        $dataEntitiesFull = $this->dataEntities;
        $dataEntitiesFull[] = $rootNode;
        // merge all, including authors
        $this->dataArr['@graph'] = array_merge($this->dataArr['@graph'], $this->getCreateActionNode(), $dataEntitiesFull, $this->authors);

        // add the metadata json file containing references to all the content of our crate
        $jsonLd = json_encode($this->dataArr, JSON_THROW_ON_ERROR, 512);
        $this->Zip->addFile($this->root . '/ro-crate-metadata.json', $jsonLd);
        // add a HTML preview file
        $this->Zip->addFile($this->root . '/ro-crate-preview.html', $this->crateToHtml($jsonLd, $rootNode));
        $this->Zip->finish();
    }

    protected function processEntityArr(): void
    {
        foreach ($this->entityArr as $entity) {
            try {
                $this->processEntity($entity);
            } catch (IllegalActionException) {
                continue;
            }
        }
    }

    protected function getRootNode(): array
    {
        // add the description of root with hasPart property
        return array(
            '@id' => './',
            'identifier' => Tools::getUuidv4(),
            '@type' => 'Dataset',
            'datePublished' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'hasPart' => $this->rootParts,
            'name' => 'eLabFTW export',
            'description' => 'This is a .eln export from eLabFTW',
            'version' => (string) self::INTERNAL_ELN_VERSION,
            'license' => array('@id' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/'),
        );
    }

    protected function crateToHtml(string $jsonLd, array $rootNode): string
    {
        // group the nodes by type and is their id as key
        $grouped = array_reduce(
            $this->dataEntities,
            function (array $carry, array $item) {
                $carry[$item['@type']][$item['@id']] = $item;
                return $carry;
            },
            array()
        );

        // ksort acts on the array itself
        ksort($grouped, SORT_STRING);
        return $this->getTwig(true)->render('eln-preview.html', array(
            'createdAt' => new DateTimeImmutable()->format(DateTimeImmutable::ATOM),
            'entities' => $grouped,
            'jsonLd' => $jsonLd,
            'rootNode' => $rootNode,
        ));
    }

    protected static function toSlug(AbstractEntity $entity): string
    {
        return sprintf('%s:%d', $entity->entityType->value, $entity->id ?? 0);
    }

    protected static function getDatasetFolderName(array $entityData): string
    {
        $prefix = '';
        if (!empty($entityData['category_title'])) {
            $prefix = Filter::forFilesystem($entityData['category_title']) . ' - ';
        }
        // prevent a zip name with too many characters, see #3966
        $prefixedTitle = mb_substr($prefix . Filter::forFilesystem($entityData['title']), 0, 103);
        // SHOULD end with /
        return sprintf('%s - %s/', $prefixedTitle, Tools::getShortElabid($entityData['elabid'] ?? ''));
    }

    protected function processEntity(AbstractEntity $entity): bool
    {
        // experiments:123 or items:123
        $slug = self::toSlug($entity);
        // only process an entity once
        if (in_array($slug, $this->processedEntities, true)) {
            return false;
        }
        $e = $entity->entityData;
        $hasPart = array();
        $currentDatasetFolder = self::getDatasetFolderName($e);
        $this->processedEntities[] = $slug;
        $this->folder = $this->root . '/' . $currentDatasetFolder;
        $this->rootParts[] = array('@id' => './' . $currentDatasetFolder);
        // COMMENTS
        $comments = array();
        foreach ($e['comments'] ?? array() as $comment) {
            // simply use some random bytes here for the id
            $hash = hash(self::HASH_ALGO, random_bytes(6));
            $id = sprintf('comment://%s?hash_algo=%s', $hash, self::HASH_ALGO);
            // we add the reference to the comment in hasPart
            $comments[] = array('@id' => $id);
            // now we build a root node for the comment, with the same id as the one referenced in the main entity
            $this->dataEntities[] = array(
                '@id' => $id,
                '@type' => 'Comment',
                'dateCreated' => (new DateTimeImmutable($comment['created_at']))->format(DateTimeImmutable::ATOM),
                'text' => $comment['comment'],
                'author' => array('@id' => $this->getAuthorId(new Users((int) $comment['userid']))),
            );
        }
        // TAGS
        $keywords = array();
        if (!empty($e['tags'] ?? array())) {
            // the keywords value is a comma separated list
            // but eLab allows comma in tags, so to prevent issues, replace all commas in tags with -
            $keywords = implode(',', explode('|', strtr((string) $e['tags'], ',', '-')));
        }

        // UPLOADS
        // ignore the uploads from entity and fetch a new list including archived uploads
        $uploadedFilesArr = $entity->Uploads->readAll(new BaseQueryParams(
            limit: PHP_INT_MAX,
            states: array(State::Normal, State::Archived),
        ));
        if (!empty($uploadedFilesArr)) {
            try {
                // this gets modified by the function so we have the correct real_names
                $uploadedFilesArr = $this->addAttachedFiles($uploadedFilesArr);
            } catch (UnableToReadFile) {
            }
            foreach ($uploadedFilesArr as $file) {
                $uploadAtId = './' . $currentDatasetFolder . $file['real_name'];
                $hasPart[] = array('@id' => $uploadAtId);
                $fileNode = array(
                    '@id' => $uploadAtId,
                    '@type' => 'File',
                    'name' => $file['real_name'],
                    'alternateName' => $file['long_name'],
                    'creativeWorkStatus' => State::from($file['state'])->name,
                    // TODO actually store content type Mime for uploaded files in that column
                    'encodingFormat' => $file['content_type'] ?? 'application/octet-stream',
                    'contentSize' => $file['filesize'],
                    'sha256' => $file['hash'] ?? hash_file('sha256', $uploadAtId),
                );
                // add the file comment as description but only if it's present
                if (!empty($file['comment'])) {
                    $fileNode['description'] = $file['comment'];
                }
                $this->dataEntities[] = $fileNode;
            }
        }
        // LINKS (mentions)
        // this array will be added to the "mentions" attribute of the main dataset
        $mentions = array();
        $linkTypes = array('experiments', 'items');
        foreach ($linkTypes as $type) {
            foreach ($e[$type . '_links'] as $link) {
                try {
                    if ($type === 'items') {
                        $link = new Items($this->requester, $link['entityid'], $this->bypassReadPermission);
                    } else {
                        $link = new Experiments($this->requester, $link['entityid'], $this->bypassReadPermission);
                    }
                    $mentions[] = array('@id' => './' . self::getDatasetFolderName($link->entityData));
                    // WARNING: recursion!
                    $this->processEntity($link);
                } catch (IllegalActionException) {
                    continue;
                }
            }
        }

        $datasetNode = array(
            '@id' => './' . $currentDatasetFolder,
            '@type' => 'Dataset',
            'author' => array('@id' => $this->getAuthorId(new Users((int) $e['userid']))),
            'dateCreated' => (new DateTimeImmutable($e['created_at']))->format(DateTimeImmutable::ATOM),
            'dateModified' => (new DateTimeImmutable($e['modified_at']))->format(DateTimeImmutable::ATOM),
            'temporal' => (new DateTimeImmutable($e['date'] ?? date('Y-m-d')))->format(DateTimeImmutable::ATOM),
            'name' => $e['title'],
            'encodingFormat' => ($e['content_type'] ?? 1) === 1 ? 'text/html' : 'text/markdown',
            'url' => Env::asUrl('SITE_URL') . '/' . $entity->entityType->toPage() . ($entity->entityType == EntityType::ItemsTypes ? '&' : '?') . 'mode=view&id=' . $e['id'],
            'genre' => $entity->entityType->toGenre(),
        );
        $datasetNode = self::addIfNotEmpty(
            $datasetNode,
            array('alternateName' => $e['custom_id'] ?? ''),
            array('comment' => $comments),
            array('creativeWorkStatus' => $e['status_title'] ?? ''),
            array('hasPart' => $hasPart),
            array('identifier' => $e['elabid'] ?? ''),
            array('keywords' => $keywords),
            array('mentions' => $mentions),
            array('text' => $e['body']),
        );
        if (!empty($e['category_title'])) {
            $categoryAtId = '#category-' . $e['category_title'];
            // only add it once
            if (!in_array($categoryAtId, array_column($this->dataEntities, '@id'), true)) {
                $this->dataEntities[] =  array(
                    '@id' => $categoryAtId,
                    '@type' => 'Thing',
                    'name' => $e['category_title'],
                    'color' => $e['category_color'],
                );
            }
            $datasetNode['about'] = array('@id' => $categoryAtId);
        }
        // METADATA (extra fields)
        if ($e['metadata']) {
            $processedMetadata = $this->metadataToJsonLd($e['metadata']);
            $datasetNode['variableMeasured'] = $processedMetadata['ids'];
            array_push($this->dataEntities, ...$processedMetadata['nodes']);
        }
        // RATING
        if (!empty($e['rating'])) {
            $datasetNode['aggregateRating'] = array(
                '@id' => 'rating://' . Tools::getUuidv4(),
                '@type' => 'AggregateRating',
                'ratingValue' => $e['rating'],
                'reviewCount' => 1,
            );
        }
        // STEPS
        if (!empty($e['steps'])) {
            // $datasetNode['step'] = $this->stepsToJsonLd($e['steps']);
            $processedSteps = $this->stepsToJsonLd($e['steps']);
            $datasetNode['step'] = $processedSteps['ids'];
            array_push($this->dataEntities, ...$processedSteps['nodes']);
        }

        $this->dataEntities[] = $datasetNode;
        return true;
    }

    protected static function addIfNotEmpty(array $datasetNode, array ...$nameValueArr): array
    {
        foreach ($nameValueArr as $nameValue) {
            $key = array_key_first($nameValue);
            if ($key === null) {
                continue;
            }
            if (!empty($nameValue[$key])) {
                $datasetNode[$key] = $nameValue[$key];
            }
        }
        return $datasetNode;
    }

    protected function stepsToJsonLd(array $steps): array
    {
        // we will return two arrays, the array of @id, and an array of nodes of @type HowToStep
        $res = array('ids' => array(), 'nodes' => array());
        foreach ($steps as $step) {
            $id = 'howtostep://' . Tools::getUuidv4();
            $res['ids'][] = array('@id' => $id);
            $node = array(
                '@id' => $id,
                '@type' => 'HowToStep',
                'position' => $step['ordering'],
                'creativeWorkStatus' => $step['finished'] === 1 ? 'finished' : 'unfinished',
            );
            if ($step['deadline']) {
                $node['expires'] = (new DateTimeImmutable($step['deadline']))->format(DateTimeImmutable::ATOM);
            }
            if ($step['finished_time']) {
                $node['temporal'] = (new DateTimeImmutable($step['finished_time']))->format(DateTimeImmutable::ATOM);
            }
            $stepBodyId = 'howtodirection://' . Tools::getUuidv4();
            $node['itemListElement'] = array('@id' => $stepBodyId);
            $res['nodes'][] = $node;
            // step body is in another node
            $res['nodes'][] = array(
                '@id' => $stepBodyId,
                '@type' => 'HowToDirection',
                'text' => $step['body'],
            );
        }
        return $res;
    }

    protected function metadataToJsonLd(string $strMetadata): array
    {
        $metadata = json_decode($strMetadata, true, 42, JSON_THROW_ON_ERROR);
        // we will return two arrays, the array of @id, and an array of nodes of @type PropertyValue
        $res = array('ids' => array(), 'nodes' => array());

        // add one that contains all the original metadata as string
        $id = 'pv://' . Tools::getUuidv4();
        $res['ids'][] = array('@id' => $id);
        $res['nodes'][] = array(
            '@id' => $id,
            '@type' => 'PropertyValue',
            'propertyID' => 'elabftw_metadata',
            'description' => 'eLabFTW metadata JSON as string',
            'value' => $strMetadata,
        );

        // stop here if there are no extra fields
        if (empty($metadata[Metadata::ExtraFields->value])) {
            return $res;
        }
        // now add one for all the extra fields
        foreach ($metadata[Metadata::ExtraFields->value] as $name => $props) {
            if (!array_key_exists('value', $props)) {
                $props['value'] = null;
            } elseif ($props['value'] === '') {
                $props['value'] = null;
            }
            // https://schema.org/PropertyValue
            $id = 'pv://' . Tools::getUuidv4();
            $res['ids'][] = array('@id' => $id);

            $pv = array();
            $pv['@type'] = 'PropertyValue';
            $pv['@id'] = $id;
            $pv['propertyID'] = $name;
            $pv['valueReference'] = $props['type'];
            $pv['value'] = $props['value'] ?? '';
            if (!empty($props['description'])) {
                $pv['description'] = $props['description'];
            }
            if (!empty($props['unit'])) {
                $pv['unitText'] = $props['unit'];
            }
            $res['nodes'][] = $pv;
        }
        return $res;
    }

    /**
     * Generate an author node unless it exists already
     */
    protected function getAuthorId(Users $author): string
    {
        // add firstname and lastname to the hash to get more entropy. Use the userid too so similar names won't collide.
        $hash = hash(
            self::HASH_ALGO,
            (string) $author->userid . $author->userData['firstname'] . $author->userData['lastname'] . $author->userData['email'],
        );
        $id = sprintf('person://%s?hash_algo=%s', $hash, self::HASH_ALGO);
        $node = array(
            '@id' => $id,
            '@type' => 'Person',
            'givenName' => $author->userData['firstname'],
            'familyName' => $author->userData['lastname'],
            'email' => $author->userData['email'],
        );
        // only add an identifier property if there is an orcid
        if (!empty($author->userData['orcid'])) {
            $node['identifier'] = 'https://orcid.org/' . $author->userData['orcid'];
        }
        // only add it if it doesn't exist yet in our list of authors
        if (!in_array($id, array_column($this->authors, '@id'), true)) {
            $this->authors[] = $node;
        }
        return $id;
    }
}
