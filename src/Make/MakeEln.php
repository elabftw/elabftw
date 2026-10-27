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
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Hash\FileHash;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Items;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\OpenBabelFingerprinter;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\TwigTrait;
use League\Flysystem\UnableToReadFile;
use GuzzleHttp\Client;
use ZipStream\ZipStream;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function array_push;
use function ksort;
use function array_column;
use function array_key_exists;
use function array_key_first;
use function array_merge;
use function array_reduce;
use function date;
use function explode;
use function fclose;
use function fopen;
use function hash;
use function hash_file;
use function implode;
use function in_array;
use function json_decode;
use function json_encode;
use function random_bytes;
use function sprintf;
use function strtr;
use function array_map;
use function array_values;

/**
 * Make an ELN archive
 */
class MakeEln extends AbstractMakeEln
{
    use TwigTrait;

    public function __construct(
        protected LoggerInterface $logger,
        ZipStream $Zip,
        protected Users $requester,
        protected array $entityArr,
        protected Instance2Rors $instance2Rors,
        protected Teams2Rors $teams2Rors,
        protected Users2Rors $users2Rors,
        protected bool $includeLinkedEntities = true,
        protected bool $includeChangelog = true,
    ) {
        parent::__construct($Zip, $instance2Rors);
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

    public function writeToFile(string $absolutePath): void
    {
        $fileStream = fopen($absolutePath, 'wb');
        if ($fileStream === false) {
            throw new RuntimeException('Could not open output stream!');
        }
        $this->Zip = new ZipStream(outputStream: $fileStream, sendHttpHeaders: false);
        $this->getStreamZip();
        fclose($fileStream);
    }

    protected function processEntityArr(): void
    {
        foreach ($this->entityArr as $entity) {
            try {
                $this->processEntity($entity);
            } catch (ForbiddenException) {
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
            'conformsTo' => array(
                '@id' => 'https://purl.org/elnconsortium/eln-spec/1.2+20260923',
            ),
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

    protected static function getDatasetFolderName(): string
    {
        // SHOULD end with /
        return sprintf('%s/', Tools::getUuidv4());
    }

    // returns the datasetFolder
    protected function processEntity(AbstractEntity $entity): string | false
    {
        // experiments:123 or items:123
        $slug = self::toSlug($entity);
        // only process an entity once, but still return its folder so links to already processed entities are preserved
        if (in_array($slug, $this->processedEntities, true)) {
            return $this->processedEntityFolders[$slug] ?? false;
        }
        $e = $entity->entityData;
        $hasPart = array();
        $currentDatasetFolder = self::getDatasetFolderName();
        $this->processedEntities[] = $slug;
        $this->processedEntityFolders[$slug] = $currentDatasetFolder;
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

        $metadataReferences = array(
            'compounds' => array(),
            'experiments' => array(),
            'items' => array(),
            'users' => array(),
        );

        // COMPOUNDS
        $compounds = array();
        foreach ($e['compounds_links'] ?? array() as $compound) {
            $reference = $this->compoundToJsonLd($compound);
            $compounds[] = $reference;
            $metadataReferences['compounds'][$compound['id']] = $reference;
        }

        // TAGS
        $keywords = array();
        if (!empty($e['tags'] ?? array())) {
            // the keywords value is a comma separated list
            // but eLab allows comma in tags, so to prevent issues, replace all commas in tags with -
            $keywords = implode(',', explode('|', strtr((string) $e['tags'], ',', '-')));
        }

        // UPLOADS
        $uploadGroups = $this->groupsToJsonLd($e['upload_groups'] ?? array(), 'Collection', './' . $currentDatasetFolder);
        // ignore the uploads from entity and fetch a new list including archived uploads
        $uploadedFilesArr = $entity->Uploads->readAll(new BaseQueryParams(
            limit: PHP_INT_MAX,
            states: array(State::Normal, State::Archived),
        ));
        if (!empty($uploadedFilesArr)) {
            try {
                // this gets modified by the function so we have the correct real_names
                $uploadedFilesArr = $this->addAttachedFiles($uploadedFilesArr);
            } catch (UnableToReadFile $ex) {
                $this->logger->error($ex->getMessage());
            }
            foreach ($uploadedFilesArr as $file) {
                $uploadAtId = './' . $currentDatasetFolder . $file['uuid'];
                $hasPart[] = array('@id' => $uploadAtId);
                $fileNode = array(
                    '@id' => $uploadAtId,
                    '@type' => 'File',
                    'name' => $file['real_name'],
                    'alternateName' => $file['long_name'],
                    'creativeWorkStatus' => State::from($file['state'])->name,
                    // TODO actually store content type Mime for uploaded files in that column
                    'encodingFormat' => $file['content_type'] ?? 'application/octet-stream',
                    // cast to string because that's what the ELN spec expects
                    'contentSize' => (string) $file['filesize'],
                    'sha256' => $file['hash'] ?? hash_file('sha256', $uploadAtId),
                );
                if (isset($file['ordering'])) {
                    $fileNode['position'] = (int) $file['ordering'];
                }
                $groupId = $file['group_id'] ?? null;
                if ($groupId !== null && isset($uploadGroups['ids'][$groupId])) {
                    $fileNode['isPartOf'] = $uploadGroups['ids'][$groupId];
                    $uploadGroups['nodes'][$groupId]['hasPart'][] = array('@id' => $uploadAtId);
                }
                // add the file comment as description but only if it's present
                if (!empty($file['comment'])) {
                    $fileNode['description'] = $file['comment'];
                }
                $this->dataEntities[] = $fileNode;
            }
        }
        // Keep files directly in hasPart for older importers, and also describe their groups.
        $hasPart = array_merge($hasPart, array_values($uploadGroups['ids']));
        array_push($this->dataEntities, ...array_values($uploadGroups['nodes']));

        // LINKS (mentions)
        // this array will be added to the "mentions" attribute of the main dataset
        $mentions = array();
        if ($this->includeLinkedEntities) {
            foreach (array('experiments', 'items') as $type) {
                $mentions = array_merge(
                    $mentions,
                    $this->processEntityLinks($e[$type . '_links'] ?? array(), $type, true),
                );
            }
            // RELATED LINKS
            // These are entities linking to the current one. Process them so their own mentions restore the original direction.
            $relatedLinkTypes = array(
                'related_experiments_links' => 'experiments',
                'related_items_links' => 'items',
            );
            foreach ($relatedLinkTypes as $key => $type) {
                $this->processEntityLinks($e[$key] ?? array(), $type, false);
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
            array('compounds_links' => $compounds),
            array('conditionsOfAccess' => $e['locked'] === 1 ? 'Locked' : 'Unlocked'),
            array('creativeWorkStatus' => $e['status_title'] ?? ''),
            array('subjectOf' => $this->includeChangelog ? $this->changelogToUpdateActions($e['changelog'] ?? array()) : array()),
            array('status' => State::from($e['state'])->name),
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
            $metadataReferences = $this->processMetadataReferences($e['metadata'], $metadataReferences);
            $processedMetadata = $this->metadataToJsonLd($e['metadata'], $metadataReferences);
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
        if (!empty($e['steps']) || !empty($e['step_groups'])) {
            $processedSteps = $this->stepsToJsonLd($e['steps'] ?? array(), $e['step_groups'] ?? array(), './' . $currentDatasetFolder);
            if ($processedSteps['ids'] !== array()) {
                $datasetNode['step'] = $processedSteps['ids'];
            }
            $datasetNode['hasPart'] = array_merge($datasetNode['hasPart'] ?? array(), $processedSteps['group_ids']);
            array_push($this->dataEntities, ...$processedSteps['nodes']);
        }

        $this->dataEntities[] = $datasetNode;
        return $currentDatasetFolder;
    }

    #[Override]
    protected function addAttachedFiles($filesArr): array
    {
        foreach ($filesArr as &$file) {
            $storageFs = Storage::from($file['storage'])->getStorage()->getFs();
            // make sure we have a hash
            if (empty($file['hash'])) {
                $hasher = new FileHash($storageFs, $file['long_name']);
                $file['hash'] = $hasher->getSafeHash();
            }
            // add files to archive
            $file['uuid'] = Tools::getUuidv4();
            $this->addAttachedFileInZip($this->folder . $file['uuid'], $storageFs->readStream($file['long_name']));
        }
        return $filesArr;
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

    /**
     * Use contextual entities rather than extra directories or database IDs for groups.
     * Empty groups are exported too. Their position records the named-group order.
     */
    protected function groupsToJsonLd(array $groups, string $type, string $datasetId): array
    {
        $res = array('ids' => array(), 'nodes' => array());
        $membersProperty = $type === 'HowToSection' ? 'itemListElement' : 'hasPart';
        foreach ($groups as $group) {
            $id = '#group-' . Tools::getUuidv4();
            $res['ids'][$group['id']] = array('@id' => $id);
            $res['nodes'][$group['id']] = array(
                '@id' => $id,
                '@type' => $type,
                'name' => $group['title'],
                'position' => (int) $group['ordering'],
                'isPartOf' => array('@id' => $datasetId),
                $membersProperty => array(),
            );
        }
        return $res;
    }

    protected function stepsToJsonLd(array $steps, array $groups = array(), string $datasetId = './'): array
    {
        // Keep the flat step references and add sections describing their group membership.
        $stepGroups = $this->groupsToJsonLd($groups, 'HowToSection', $datasetId);
        $res = array('ids' => array(), 'nodes' => array(), 'group_ids' => array_values($stepGroups['ids']));
        foreach ($steps as $step) {
            $id = 'howtostep://' . Tools::getUuidv4();
            $res['ids'][] = array('@id' => $id);
            $node = array(
                '@id' => $id,
                '@type' => 'HowToStep',
                'position' => $step['ordering'],
                'creativeWorkStatus' => $step['finished'] === 1 ? 'finished' : 'unfinished',
            );
            $groupId = $step['group_id'] ?? null;
            if ($groupId !== null && isset($stepGroups['ids'][$groupId])) {
                $node['isPartOf'] = $stepGroups['ids'][$groupId];
                $stepGroups['nodes'][$groupId]['itemListElement'][] = array('@id' => $id);
            }
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
        array_push($res['nodes'], ...array_values($stepGroups['nodes']));
        return $res;
    }

    protected function metadataToJsonLd(string $strMetadata, array $metadataReferences = array()): array
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
            if (in_array($props['type'], array('compounds', 'experiments', 'items', 'users'), true) && !empty($props['value'])) {
                $sourceId = (int) $props['value'];
                $reference = $metadataReferences[$props['type']][$sourceId] ?? null;
                if ($reference !== null) {
                    $pv['value'] = $reference;
                }
            }
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

    protected function getUserRors(Users $user): array
    {
        $teamsRors = array();
        $UsersHelper = new UsersHelper($user->getUserid());
        $teams = $UsersHelper->getTeamsIdFromUserid();
        foreach ($teams as $teamid) {
            $teamsRors = array_merge($teamsRors, $this->teams2Rors->readAllFromId($teamid));
        }

        return array_map(
            static fn(array $row): array => array(
                '@id' => 'https://ror.org/' . $row['ror'],
            ),
            array_merge($this->users2Rors->readAllFromId($user->getUserid()), $teamsRors)
        );
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
            'affiliation' => $this->getUserRors($author),
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

    private function processMetadataReferences(string $strMetadata, array $references): array
    {
        $metadata = json_decode($strMetadata, true, 42, JSON_THROW_ON_ERROR);
        foreach ($metadata[Metadata::ExtraFields->value] ?? array() as $props) {
            $type = $props['type'] ?? '';
            $sourceId = (int) $props['value'];
            if (isset($references[$type][$sourceId])) {
                continue;
            }
            $reference = match ($type) {
                'compounds' => $this->processMetadataCompounds($sourceId),
                'experiments' => $this->processMetadataEntity($sourceId, EntityType::Experiments),
                'items' => $this->processMetadataEntity($sourceId, EntityType::Items),
                'users' => $this->processMetadataUser($sourceId),
                default => null,
            };
            if ($reference !== null) {
                $references[$type][$sourceId] = $reference;
            }
        }
        return $references;
    }

    private function processMetadataUser(int $userId): array
    {
        return array('@id' => $this->getAuthorId(new Users($userId)));
    }

    private function processMetadataEntity(int $entityId, EntityType $entityType): ?array
    {
        $Entity = $entityType->toInstance(
            $this->requester,
            $entityId,
            $this->bypassReadPermission,
        );
        $datasetFolder = $this->processEntity($Entity);
        if ($datasetFolder === false) {
            return null;
        }
        return array('@id' => './' . $datasetFolder);
    }

    private function processMetadataCompounds(int $compoundId): array
    {
        // Initialize the model only when a compound needs to be loaded.
        $Config = Config::getConfig();
        $httpGetter = new HttpGetter(
            new Client(),
            $Config->configArr['proxy'],
            !Env::asBool('DEV_MODE'),
        );
        $Compounds = new Compounds(
            $httpGetter,
            $this->requester,
            new OpenBabelFingerprinter(),
        );

        $Compounds->setId($compoundId);
        return $this->compoundToJsonLd($Compounds->readOne());
    }

    private function compoundToJsonLd(array $compound): array
    {
        $id = sprintf('#compound-%d', $compound['id']);
        $reference = array('@id' => $id);
        // A compound may be referenced by several exported datasets,
        // but each '@id' must occur only once in the graph.
        if (in_array($id, array_column($this->dataEntities, '@id'), true)) {
            return $reference;
        }
        $identifiers = array();
        if (!empty($compound['cas_number'])) {
            $identifiers[] = array(
                '@type' => 'PropertyValue',
                'propertyID' => 'CAS Registry Number',
                'value' => $compound['cas_number'],
            );
        }
        if (!empty($compound['pubchem_cid'])) {
            $identifiers[] = array(
                '@type' => 'PropertyValue',
                'propertyID' => 'PubChem CID',
                'value' => $compound['pubchem_cid'],
                'sameAs' => sprintf('https://pubchem.ncbi.nlm.nih.gov/#query=%d', $compound['pubchem_cid']),
            );
        }
        $this->dataEntities[] = array(
            '@id' => $id,
            '@type' => 'MolecularEntity',
            'name' => $compound['name'],
            'molecularFormula' => $compound['molecular_formula'],
            'inChI' => $compound['inchi'],
            'inChIKey' => $compound['inchi_key'],
            'iupacName' => $compound['iupac_name'],
            'molecularWeight' => $compound['molecular_weight'],
            'smiles' => $compound['smiles'],
            'identifier' => $identifiers,
        );
        return $reference;
    }

    // convert changelog actions to schema.org valid fields UpdateAction
    private function changelogToUpdateActions(array $changelog): array
    {
        $actions = array();

        foreach ($changelog as $log) {
            $actions[] = array(
                '@id' => 'updateaction://' . Tools::getUuidv4(),
                '@type' => 'UpdateAction',
                'agent' => array('@id' => $this->getAuthorId(new Users($log['userid']))),
                'object' => $log['target'] ?? '',
                'result' => $log['content'] ?? '',
                'startTime' => new DateTimeImmutable($log['created_at'])->format(DateTimeImmutable::ATOM),
            );
        }
        return $actions;
    }

    // Process entity links and optionally return them as RO-Crate mentions.
    private function processEntityLinks(array $links, string $type, bool $asMentions): array
    {
        $mentions = array();
        foreach ($links as $link) {
            try {
                if ($type === 'items') {
                    $linkedEntity = new Items($this->requester, $link['entityid'], $this->bypassReadPermission);
                } else {
                    $linkedEntity = new Experiments($this->requester, $link['entityid'], $this->bypassReadPermission);
                }
                // WARNING: recursion!
                $linkAtId = $this->processEntity($linkedEntity);
                if ($asMentions && $linkAtId !== false) {
                    $mentions[] = array('@id' => './' . $linkAtId);
                }
            } catch (ForbiddenException $ex) {
                $this->logger->warning(sprintf(
                    'Skipping linked entity %s:%d during ELN export: %s',
                    $type,
                    $link['entityid'],
                    $ex->getMessage(),
                ));
            }
        }
        return $mentions;
    }
}
