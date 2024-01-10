<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use League\Flysystem\UnableToReadFile;
use ZipStream\ZipStream;

/**
 * Make an ELN archive
 */
class MakeEln extends MakeStreamZip
{
    private const HASH_ALGO = 'sha256';

    protected string $extension = '.eln';

    private array $authors = array();

    // name of the folder containing everything
    private string $root;

    public function __construct(protected ZipStream $Zip, AbstractEntity $entity, protected array $idArr)
    {
        parent::__construct($Zip, $entity, $idArr);
        $now = new DateTimeImmutable();
        $this->root = $now->format('Y-m-d-His') . '-export';
        $this->jsonArr = array(
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => array(
                array(
                    '@id' => 'ro-crate-metadata.json',
                    '@type' => 'CreativeWork',
                    'about' => array('@id' => './'),
                    'conformsTo' => array('@id' => 'https://w3id.org/ro/crate/1.1'),
                    'dateCreated' => $now->format(DateTimeImmutable::ATOM),
                    'sdPublisher' => array(
                        '@type' => 'Organization',
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
        $dataEntities = array();
        // an array of "@id" for main datasets
        $rootParts = array();
        foreach ($this->idArr as $id) {
            $hasPart = array();
            try {
                $this->Entity->setId((int) $id);
            } catch (IllegalActionException $e) {
                continue;
            }
            $e = $this->Entity->entityData;
            $currentDatasetFolder = $this->getBaseFileName();
            $this->folder = $this->root . '/' . $currentDatasetFolder;
            $rootParts[] = array('@id' => './' . $currentDatasetFolder);

            // LINKS (mentions)
            // this array will be added to the "mentions" attribute of the main dataset
            $mentions = array();
            foreach ($e['items_links'] as $link) {
                $id = Config::fromEnv('SITE_URL') . '/database.php?mode=view&id=' . $link['itemid'];
                $mentions[] = array('@id' => $id);
                $dataEntities[] = array(
                    '@id' => $id,
                    '@type' => 'Dataset',
                    'name' => ($link['category'] ?? '') . ' - ' . $link['title'],
                    'identifier' => $link['elabid'],
                );
            }
            foreach ($e['experiments_links'] as $link) {
                $id = Config::fromEnv('SITE_URL') . '/experiments.php?mode=view&id=' . $link['itemid'];
                $mentions[] = array('@id' => $id);
                $dataEntities[] = array(
                    '@id' => $id,
                    '@type' => 'Dataset',
                    'name' => ($link['category'] ?? '') . ' - ' . $link['title'],
                    'identifier' => $link['elabid'],
                );
            }

            // JSON
            $MakeJson = new MakeJson($this->Entity, array((int) $e['id']));
            $json = $MakeJson->getFileContent();
            $this->Zip->addFile($this->folder . '/' . $MakeJson->getFileName(), $json);
            $jsonAtId = './' . $currentDatasetFolder . '/' . $MakeJson->getFileName();
            // add the json in the hasPart of the entry
            $hasPart[] = array('@id' => $jsonAtId);
            $dataEntities[] = array(
                '@id' => $jsonAtId,
                '@type' => 'File',
                'description' => 'JSON export',
                'name' => $MakeJson->getFileName(),
                'encodingFormat' => $MakeJson->getContentType(),
                'contentSize' => (string) $MakeJson->getContentSize(),
                'sha256' => hash('sha256', $json),
            );

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
                $dataEntities[] = array(
                    '@id' => $id,
                    '@type' => 'Comment',
                    'dateCreated' => $dateCreated,
                    'text' => $comment['comment'],
                    'author' => array('@id' => $this->getAuthorId($comment['userid'], $firstname, $lastname, $comment['orcid'])),
                );
            }

            // UPLOADS
            $uploadedFilesArr = $e['uploads'];
            if (!empty($uploadedFilesArr)) {
                try {
                    // this gets modified by the function so we have the correct real_names
                    $uploadedFilesArr = $this->addAttachedFiles($uploadedFilesArr);
                } catch (UnableToReadFile $e) {
                    continue;
                }
                foreach ($uploadedFilesArr as $file) {
                    $uploadAtId = './' . $currentDatasetFolder . '/' . $file['real_name'];
                    $hasPart[] = array('@id' => $uploadAtId);
                    $dataEntities[] = array(
                        '@id' => $uploadAtId,
                        '@type' => 'File',
                        'description' => $file['comment'] ?? '',
                        'name' => $file['real_name'],
                        'alternateName' => $file['long_name'],
                        'contentSize' => $file['filesize'],
                        'sha256' => $file['hash'] ?? hash_file('sha256', $uploadAtId),
                    );
                }
            }

            // TAGS
            $keywords = array();
            if ($this->Entity->entityData['tags']) {
                // the keywords value is a comma separated list
                // let's hope no one has a comma in their tags...
                $keywords = implode(',', explode('|', (string) $this->Entity->entityData['tags']));
            }

            // MAIN ENTRY
            $firstname = $e['firstname'] ?? '';
            $lastname = $e['lastname'] ?? '';
            $dataEntities[] = array(
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
            );
        }
        // add the description of root with hasPart property
        $dataEntities[] = array(
            '@id' => './',
            '@type' => 'Dataset',
            'hasPart' => $rootParts,
        );

        // merge all, including authors
        $this->jsonArr['@graph'] = array_merge($this->jsonArr['@graph'], $dataEntities, $this->authors);

        // add the metadata json file containing references to all the content of our crate
        $this->Zip->addFile($this->root . '/ro-crate-metadata.json', json_encode($this->jsonArr, JSON_THROW_ON_ERROR, 512));
        $this->Zip->finish();
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
