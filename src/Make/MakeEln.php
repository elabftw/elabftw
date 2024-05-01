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
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Services\Filter;
use League\Flysystem\UnableToReadFile;
use ZipStream\ZipStream;

/**
 * Make an ELN archive
 */
class MakeEln extends MakeStreamZip
{
    private const string HASH_ALGO = 'sha256';

    protected string $extension = '.eln';

    private array $authors = array();

    private array $rootParts = array();

    private array $dataEntities = array();

    private array $processedEntities = array();

    private DateTimeImmutable $creationDateTime;

    // name of the folder containing everything
    private string $root;

    public function __construct(protected ZipStream $Zip, AbstractEntity $entity, protected array $idArr)
    {
        parent::__construct(
            Zip: $Zip,
            entity: $entity,
            idArr: $idArr,
            usePdfa: false,
            includeChangelog: false
        );

        $this->creationDateTime = new DateTimeImmutable();
        $this->root = $this->creationDateTime->format('Y-m-d-His') . '-export';
        $this->dataArr = array(
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => array(
                array(
                    '@id' => 'ro-crate-metadata.json',
                    '@type' => 'CreativeWork',
                    'about' => array('@id' => './'),
                    'conformsTo' => array('@id' => 'https://w3id.org/ro/crate/1.1'),
                    'dateCreated' => $this->creationDateTime->format(DateTimeImmutable::ATOM),
                    'sdPublisher' => array(
                        '@type' => 'Organization',
                        'areaServed' => 'Laniakea Supercluster',
                        'name' => 'eLabFTW',
                        'logo' => 'https://www.elabftw.net/img/elabftw-logo-only.svg',
                        'slogan' => 'A free and open source electronic lab notebook.',
                        'url' => 'https://www.elabftw.net',
                        'parentOrganization' => array(
                            '@type' => 'Organization',
                            'name' => 'Deltablot',
                            'logo' => 'https://www.deltablot.com/img/logos/deltablot.svg',
                            'slogan' => 'Open Source software for research labs.',
                            'url' => 'https://www.deltablot.com',
                        ),
                    ),
                    'version' => '1.0',
                ),
            ),
        );
    }

    public function getFileName(): string
    {
        return $this->root . $this->extension;
    }

    /**
     * Loop on each id and add it to our eln archive
     */
    public function getStreamZip(): void
    {
        // currently this->idArr is the list of ID of things we want to export
        // go over every id, add the links and normalize them
        $targetList = array();
        foreach ($this->idArr as $id) {
            $id = (int) $id;
            try {
                $this->Entity->setId($id);
            } catch (IllegalActionException) {
                continue;
            }
            $targetList[] = self::toSlug($this->Entity);
            foreach ($this->Entity->entityData['experiments_links'] as $link) {
                $targetList[] = sprintf('experiments:%d', $link['entityid']);
            }
            foreach ($this->Entity->entityData['items_links'] as $link) {
                $targetList[] = sprintf('items:%d', $link['entityid']);
            }
        }
        // then we import everything flattened, but use the "mentions" to add the links
        foreach ($targetList as $target) {
            $explodedTarget = explode(':', $target);
            $type = EntityType::from($explodedTarget[0]);
            try {
                $entity = $type->toInstance($this->Entity->Users, (int) $explodedTarget[1]);
            } catch (IllegalActionException) {
                continue;
            }
            $this->processEntity($entity);
        }
        // add the description of root with hasPart property
        $this->dataEntities[] = array(
            '@id' => './',
            '@type' => 'Dataset',
            'hasPart' => $this->rootParts,
        );

        // add a create action https://www.researchobject.org/ro-crate/1.1/provenance.html#recording-changes-to-ro-crates
        $createAction = array(
            array(
                '@id' => '#ro-crate_created',
                '@type' => 'CreateAction',
                'object' => array('@id' => './'),
                'name' => 'RO-Crate created',
                'endTime' => $this->creationDateTime->format(DateTimeImmutable::ATOM),
                'instrument' => array(
                    '@id' => 'https://www.elabftw.net',
                    '@type' => 'SoftwareApplication',
                    'name' => 'eLabFTW',
                    'version' => App::INSTALLED_VERSION,
                    'identifier' => 'https://www.elabftw.net',
                ),
                'actionStatus' =>  array(
                    '@id' => 'http://schema.org/CompletedActionStatus',
                ),
            ),
        );

        // merge all, including authors
        $this->dataArr['@graph'] = array_merge($this->dataArr['@graph'], $createAction, $this->dataEntities, $this->authors);

        // add the metadata json file containing references to all the content of our crate
        $this->Zip->addFile($this->root . '/ro-crate-metadata.json', json_encode($this->dataArr, JSON_THROW_ON_ERROR, 512));
        $this->Zip->finish();
    }

    private static function toSlug(AbstractEntity $entity): string
    {
        return sprintf('%s:%d', $entity->entityType->value, $entity->id ?? 0);
    }

    private static function getDatasetFolderName(array $entityData): string
    {
        $prefix = Filter::forFilesystem($entityData['category_title'] ?? '');
        return sprintf(
            '%s - %s - %s',
            $prefix,
            // prevent a zip name with too much characters from the title, see #3966
            substr(Filter::forFilesystem($entityData['title']), 0, 100),
            Tools::getShortElabid($entityData['elabid'] ?? ''),
        );
    }

    private function processEntity(AbstractEntity $entity): bool
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
        foreach ($e['comments'] as $comment) {
            // the comment creation date will be used as part of the id
            $dateCreated = (new DateTimeImmutable($comment['created_at']))->format(DateTimeImmutable::ATOM);
            $id = 'comment://' . urlencode($dateCreated);
            // we add the reference to the comment in hasPart
            $comments[] = array('@id' => $id);
            // now we build a root node for the comment, with the same id as the one referenced in the main entity
            $firstname = $comment['firstname'] ?? '';
            $lastname = $comment['lastname'] ?? '';
            $this->dataEntities[] = array(
                '@id' => $id,
                '@type' => 'Comment',
                'dateCreated' => $dateCreated,
                'text' => $comment['comment'],
                'author' => array('@id' => $this->getAuthorId($comment['userid'], $firstname, $lastname, $comment['orcid'])),
            );
        }
        // TAGS
        $keywords = array();
        if ($e['tags']) {
            // the keywords value is a comma separated list
            // let's hope no one has a comma in their tags...
            $keywords = implode(',', explode('|', (string) $e['tags']));
        }

        // UPLOADS
        $uploadedFilesArr = $e['uploads'];
        if (!empty($uploadedFilesArr)) {
            try {
                // this gets modified by the function so we have the correct real_names
                $uploadedFilesArr = $this->addAttachedFiles($uploadedFilesArr);
            } catch (UnableToReadFile) {
            }
            foreach ($uploadedFilesArr as $file) {
                $uploadAtId = './' . $currentDatasetFolder . '/' . $file['real_name'];
                $hasPart[] = array('@id' => $uploadAtId);
                $fileNode = array(
                    '@id' => $uploadAtId,
                    '@type' => 'File',
                    'name' => $file['real_name'],
                    'alternateName' => $file['long_name'],
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
        foreach($linkTypes as $type) {
            foreach ($e[$type . '_links'] as $link) {
                if ($type === 'items') {
                    $link = new Items($this->Entity->Users, $link['entityid']);
                } else {
                    $link = new Experiments($this->Entity->Users, $link['entityid']);
                }
                $mentions[] = array('@id' => './' . self::getDatasetFolderName($link->entityData));
                // WARNING: recursion!
                $this->processEntity($link);
            }
        }
        $firstname = $e['firstname'] ?? '';
        $lastname = $e['lastname'] ?? '';
        $datasetNode = array(
            '@id' => './' . $currentDatasetFolder,
            '@type' => 'Dataset',
            'author' => array('@id' => $this->getAuthorId($e['userid'], $firstname, $lastname, $e['orcid'])),
            'dateCreated' => (new DateTimeImmutable($e['created_at']))->format(DateTimeImmutable::ATOM),
            'dateModified' => (new DateTimeImmutable($e['modified_at']))->format(DateTimeImmutable::ATOM),
            'identifier' => $e['elabid'] ?? '',
            'comment' => $comments,
            'keywords' => $keywords,
            'name' => $e['title'],
            'text' => $e['body'] ?? '',
            'url' => Config::fromEnv('SITE_URL') . '/' . $this->Entity->page . '.php?mode=view&id=' . $e['id'],
            'hasPart' => $hasPart,
            'mentions' => $mentions,
            'additionalType' => $entity->entityType->value,
        );
        if ($e['category_title'] !== null) {
            $datasetNode['category'] = $e['category_title'];
        }
        if ($e['status_title'] !== null) {
            $datasetNode['status'] = $e['status_title'];
        }
        $this->dataEntities[] = $datasetNode;
        return true;
    }

    /**
     * Generate an author node unless it exists already
     */
    private function getAuthorId(int $userid, string $firstname, string $lastname, ?string $orcid): string
    {
        // add firstname and lastname to the hash to get more entropy. Use the userid too so similar names won't collide.
        $id = sprintf('person://%s?hash_algo=%s', hash(self::HASH_ALGO, (string) $userid . $firstname . $lastname), self::HASH_ALGO);
        $node = array(
            '@id' => $id,
            '@type' => 'Person',
            'familyName' => $lastname,
            'givenName' => $firstname,
        );
        // only add an identifier property if there is an orcid
        if ($orcid !== null) {
            $node['identifier'] = 'https://orcid.org/' . $orcid;
        }
        // only add it if it doesn't exist yet in our list of authors
        if (!in_array($id, array_column($this->authors, '@id'), true)) {
            $this->authors[] = $node;
        }
        return $id;
    }
}
