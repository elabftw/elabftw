<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Karl Krägelin
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\InvenioRdmAction;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Override;
use ZipStream\ZipStream;

use function array_column;
use function date;
use function fopen;
use function implode;
use function json_decode;
use function json_encode;
use function rtrim;
use function sprintf;
use function str_replace;

/**
 * Connect with an InvenioRDM repository
 * https://inveniordm.docs.cern.ch/
 */
final class InvenioRdm extends AbstractRest
{
    private string $host;

    private ?string $bearerToken = null;

    public function __construct(
        private readonly Users $requester,
        private readonly HttpGetter $httpGetter,
        string $host,
    ) {
        parent::__construct();
        $this->host = $this->normalizeHost($host);
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/inveniordm/';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return match (InvenioRdmAction::tryFrom($queryParams?->getQuery()->getString('action'))) {
            InvenioRdmAction::GetCommunities  => $this->getCommunities(),
            InvenioRdmAction::GetResourceTypes => $this->getResourceTypes(),
            InvenioRdmAction::GetLicenses     => $this->getLicenses(),
            default => throw new ImproperActionException(
                sprintf('Unknown "action" value. Expected one of: %s.', implode(', ', array_column(InvenioRdmAction::cases(), 'value')))
            ),
        };
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return 0;
    }

    /**
     * Full export flow: create draft → announce file → upload → commit
     */
    #[Override]
    public function patch(Action $action, array $params): array
    {
        if (!isset($params['entity']['type'], $params['entity']['id'])) {
            throw new ImproperActionException('Missing entity type or id for InvenioRDM export.');
        }
        $entityType = EntityType::tryFrom((string) $params['entity']['type'])
            ?? throw new ImproperActionException('Invalid value for entity.type');
        $entity = $entityType->toInstance($this->requester, (int) $params['entity']['id']);

        $metadata = $this->buildMetadata($params);
        $recordId = $this->createDraft($metadata);

        $filename = sprintf('export-elabftw-%s.eln', date('Y-m-d_H-i-s'));
        $this->initiateFileUpload($recordId, $filename);
        $this->uploadFile($recordId, $filename, $entity);
        $this->commitFile($recordId, $filename);

        $draftUrl = sprintf('%suploads/%s', rtrim(str_replace('/api/', '/', $this->host), '/') . '/', $recordId);
        return array('id' => $recordId, 'draftUrl' => $draftUrl);
    }

    private function normalizeHost(string $host): string
    {
        $host = rtrim($host, '/');
        if ($host === '') {
            throw new ImproperActionException('InvenioRDM host is not configured. Contact your system administrator.');
        }
        return $host . '/api/';
    }

    private function getBearerToken(): string
    {
        if ($this->bearerToken !== null) {
            return $this->bearerToken;
        }
        $encrypted = $this->requester->userData['inveniordm_token'] ?? '';
        if (empty($encrypted)) {
            throw new ImproperActionException(_('InvenioRDM API token is not configured. Please add your personal token in your user preferences.'));
        }
        $this->bearerToken = Crypto::decrypt($encrypted, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        return $this->bearerToken;
    }

    private function getAuthHeaders(): array
    {
        return array('Authorization' => 'Bearer ' . $this->getBearerToken());
    }

    private function createDraft(array $metadata): string
    {
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json';
        $res = $this->httpGetter->post($this->host . 'records', array(
            'headers' => $headers,
            'json' => $metadata,
        ));
        $data = json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (empty($data['id'])) {
            throw new ImproperActionException('InvenioRDM did not return a record ID.');
        }
        return (string) $data['id'];
    }

    private function initiateFileUpload(string $recordId, string $filename): void
    {
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json';
        $this->httpGetter->post(
            sprintf('%srecords/%s/draft/files', $this->host, $recordId),
            array('headers' => $headers, 'json' => array(array('key' => $filename)))
        );
    }

    private function uploadFile(string $recordId, string $filename, AbstractEntity $entity): void
    {
        $tmpFileName = Tools::getUuidv4();
        $storage = Storage::EXPORTS->getStorage();
        $absolutePath = $storage->getAbsoluteUri($tmpFileName);
        $maker = new MakeEln(App::getDefaultLogger(), new ZipStream(sendHttpHeaders: false), $this->requester, array($entity));
        $maker->writeToFile($absolutePath);

        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/octet-stream';
        try {
            $this->httpGetter->put(
                sprintf('%srecords/%s/draft/files/%s/content', $this->host, $recordId, $filename),
                array('headers' => $headers, 'body' => fopen($absolutePath, 'rb'))
            );
        } finally {
            $storage->getFs()->delete($absolutePath);
        }
    }

    private function commitFile(string $recordId, string $filename): void
    {
        $headers = $this->getAuthHeaders();
        $this->httpGetter->post(
            sprintf('%srecords/%s/draft/files/%s/commit', $this->host, $recordId, $filename),
            array('headers' => $headers)
        );
    }

    private function getCommunities(): array
    {
        $res = $this->httpGetter->get($this->host . 'communities', $this->getAuthHeaders());
        return json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getResourceTypes(): array
    {
        $res = $this->httpGetter->get($this->host . 'vocabularies/resourcetypes', $this->getAuthHeaders());
        return json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getLicenses(): array
    {
        $res = $this->httpGetter->get($this->host . 'vocabularies/licenses', $this->getAuthHeaders());
        return json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function buildMetadata(array $params): array
    {
        $metadata = array(
            'metadata' => array(
                'title' => (string) ($params['title'] ?? ''),
                'publication_date' => (string) ($params['date'] ?? date('Y-m-d')),
                'resource_type' => array('id' => (string) ($params['resource_type'] ?? '')),
                'creators' => array(
                    array(
                        'person_or_org' => array(
                            'type' => 'personal',
                            'name' => (string) ($params['author'] ?? ''),
                        ),
                    ),
                ),
                'description' => (string) ($params['description'] ?? ''),
                'rights' => array(array('id' => (string) ($params['license'] ?? ''))),
            ),
            'access' => array(
                'record' => 'public',
                'files' => 'public',
            ),
        );

        if (!empty($params['community'])) {
            $metadata['parent'] = array('communities' => array('ids' => array((string) $params['community'])));
        }

        return $metadata;
    }
}
