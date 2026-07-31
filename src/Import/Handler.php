<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\AuditEvent\Import as AuditEventImport;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Services\TeamsHelper;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Users\Users;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function _;
use function implode;
use function sprintf;

/**
 * Handle import request
 */
final class Handler extends AbstractRest
{
    private const array ALLOWED_EXTENSIONS = array('.eln', '.csv');

    private const int AUDIT_THRESHOLD = 12;

    public function __construct(private Users $requester, private LoggerInterface $logger) {}

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return array(
            'allowed_extensions' => self::ALLOWED_EXTENSIONS,
            'max_filesize' => UploadedFile::getMaxFilesize(),
            'max_upload_size' => Env::asString('MAX_UPLOAD_SIZE'),
            'max_upload_time' => Env::asInt('MAX_UPLOAD_TIME'),
        );
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $Importer = $this->getImporter($reqBody);
        $Importer->import();
        $inserted = $Importer->getInserted();
        if ($inserted > self::AUDIT_THRESHOLD) {
            /** @psalm-suppress RedundantCast had an error during eln import where userid was a string for some reason... */
            AuditLogs::create(new AuditEventImport((int) ($this->requester->userid ?? 0), $inserted));
        }
        return $inserted;
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/import/';
    }

    private function getImporter(array $reqBody): ImportInterface
    {
        $requesterId = $this->requester->getUserid();
        $destinationTeam = $this->requester->getTeam();
        // The API sends owner=0 when omitted, while direct PHP calls may not provide
        // the key at all. In both cases, default to the authenticated requester
        $owner = (int) ($reqBody['owner'] ?? 0);
        if ($owner === 0) {
            $owner = $requesterId;
        }
        // keep the authenticated requester separate from the selected record owner!
        $ImportUser = $this->requester;
        if ($owner !== $requesterId) {
            $TeamsHelper = new TeamsHelper($destinationTeam);
            // check if admin in that team. Only admin in destination team can select another owner
            if (!$TeamsHelper->isAdminInTeam($requesterId)) {
                throw new IllegalActionException('Only an administrator in the destination team may select another owner.');
            }
            if (!$TeamsHelper->isUserInTeam($owner)) {
                throw new UnprocessableContentException('The selected owner must belong to the destination team.');
            }
            $ImportUser = new Users($owner, $destinationTeam, $this->requester);
        }
        $canreadBase = BasePermissions::tryFrom((int) ($reqBody['canread_base'] ?? BasePermissions::Team->value)) ?? BasePermissions::Team;
        $canwriteBase = BasePermissions::tryFrom((int) ($reqBody['canwrite_base'] ?? BasePermissions::User->value)) ?? BasePermissions::User;
        switch ($reqBody['file']->getClientOriginalExtension()) {
            case 'eln':
                return new Eln(
                    $ImportUser,
                    $reqBody['file'],
                    Storage::CACHE->getStorage()->getFs(),
                    $this->logger,
                    EntityType::tryFrom((string) $reqBody['entity_type']),
                    category: (int) $reqBody['category'],
                    canreadBase: $canreadBase,
                    canwriteBase: $canwriteBase,
                );
            case 'csv':
                $csvTemplate = empty($reqBody['template']) ? null : (int) $reqBody['template'];
                return new Csv(
                    $ImportUser,
                    $reqBody['file'],
                    logger: $this->logger,
                    entityType: EntityType::tryFrom((string) $reqBody['entity_type']) ?? EntityType::Items,
                    category: (int) $reqBody['category'],
                    canreadBase: $canreadBase,
                    canwriteBase: $canwriteBase,
                    template: $csvTemplate,
                );
            default:
                throw new ImproperActionException(sprintf(
                    _('Error: invalid file extension for import. Allowed extensions: %s.'),
                    implode(', ', self::ALLOWED_EXTENSIONS)
                ));
        }
    }
}
