<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use League\Flysystem\UnableToReadFile;
use const SITE_URL;
use ZipStream\ZipStream;

/**
 * Make an ELN archive
 */
class MakeEln extends MakeStreamZip
{
    protected string $extension = '.eln';

    private DateTimeImmutable $now;

    // name of the folder containing everything
    private string $root;

    public function __construct(protected ZipStream $Zip, AbstractEntity $entity, protected array $idArr)
    {
        parent::__construct($Zip, $entity, $idArr);
        $this->now = new DateTimeImmutable();
        $this->root = $this->now->format('Y-m-d-His') . '-export';
        $this->jsonArr = array(
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => array(
                array(
                    '@id' => 'ro-crate-metadata.json',
                    '@type' => 'CreativeWork',
                    'about' => array('@id' => './'),
                    'conformsTo' => array('@id' => 'https://w3id.org/ro/crate/1.1'),
                    'dateCreated' => $this->now->format(DateTimeImmutable::ISO8601),
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
            $orcid = '';
            if ($e['orcid'] !== null) {
                $orcid = 'https://orcid.org/' . $e['orcid'];
            }

            // LINKS
            $mentions = array();
            foreach ($e['links'] as $link) {
                $mentions[] = array(
                    '@id' => SITE_URL . '/database.php?mode=view&id=' . $link['itemid'],
                    '@type' => 'Dataset',
                    'name' => $link['name'] . ' - ' . $link['title'],
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
                'contentType' => $MakeJson->getContentType(),
                'contentSize' => (string) $MakeJson->getContentSize(),
                'sha256' => hash('sha256', $json),
            );

            // COMMENTS
            $comments = array();
            foreach ($e['comments'] as $comment) {
                $comments[] = array(
                    'dateCreated' => (new DateTimeImmutable($e['created_at']))->format(DateTimeImmutable::ISO8601),
                    'text' => $comment['comment'],
                    'author' => array(
                        '@type' => 'Person',
                        'familyName' => $comment['lastname'] ?? '',
                        'givenName' => $comment['firstname'] ?? '',
                        'identifier' => $this->formatOrcid($e['orcid']),
                    ),
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
                        'contentSize' => $file['filesize'],
                        'sha256' => $file['hash'],
                    );
                }
            }

            // TAGS
            $keywords = array();
            if ($this->Entity->entityData['tags']) {
                $keywords = explode('|', (string) $this->Entity->entityData['tags']);
            }

            // MAIN ENTRY
            $dataEntities[] = array(
                '@id' => './' . $currentDatasetFolder,
                '@type' => 'Dataset',
                'author' => array(
                    '@type' => 'Person',
                    'familyName' => $e['lastname'] ?? '',
                    'givenName' => $e['firstname'] ?? '',
                    'identifier' => $this->formatOrcid($e['orcid']),
                ),
                'dateCreated' => (new DateTimeImmutable($e['created_at']))->format(DateTimeImmutable::ISO8601),
                'dateModified' => (new DateTimeImmutable($e['modified_at']))->format(DateTimeImmutable::ISO8601),
                'identifier' => $e['elabid'] ?? '',
                'comment' => $comments,
                'keywords' => $keywords,
                'name' => $e['title'],
                'text' => $e['body'] ?? '',
                'url' => SITE_URL . '/' . $this->Entity->page . '.php?mode=view&id=' . $e['id'],
                'hasPart' => $hasPart,
                'mentions' => $mentions,
            );
        }
        // add the description of root with hasPart property
        $dataEntities[] = array(
            '@id' => './',
            '@type' => array('Dataset'),
            'hasPart' => $rootParts,
        );
        $this->jsonArr['@graph'] = array_merge($this->jsonArr['@graph'], $dataEntities);

        // add the metadata json file containing references to all the content of our crate
        $this->Zip->addFile($this->root . '/ro-crate-metadata.json', json_encode($this->jsonArr, JSON_THROW_ON_ERROR, 512));
        $this->Zip->finish();
    }

    private function formatOrcid(?string $orcid): ?string
    {
        if ($orcid === null) {
            return null;
        }
        return 'https://orcid.org/' . $orcid;
    }
}
