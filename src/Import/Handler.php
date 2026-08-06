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
        AuditLogs::create(new AuditEventImport($this->requester->getUserid(), $Importer->getTargetUserid(), $inserted));
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
        // the key at all.
        $targetUserid = (int) ($reqBody['owner'] ?? 0);
        // fallback to requester userid if no specific owner is requested.
        if ($targetUserid === 0) {
            $targetUserid = $requesterId;
        }
        $targetUser = new Users($targetUserid, $destinationTeam);

        $TeamsHelper = new TeamsHelper($destinationTeam);
        // add additional checks if user imports as another user
        if ($targetUserid !== $requesterId) {
            // check if admin in that team. Only admin in destination team can select another owner
            if (!$TeamsHelper->isAdminInTeam($requesterId)) {
                throw new IllegalActionException('Only an administrator in the destination team may select another owner.');
            }
            if (!$TeamsHelper->isUserInTeam($targetUserid)) {
                throw new UnprocessableContentException('The selected owner must belong to the destination team.');
            }
        }
        $canreadBase = BasePermissions::tryFrom((int) ($reqBody['canread_base'] ?? BasePermissions::Team->value)) ?? BasePermissions::Team;
        $canwriteBase = BasePermissions::tryFrom((int) ($reqBody['canwrite_base'] ?? BasePermissions::User->value)) ?? BasePermissions::User;
        switch ($reqBody['file']->getClientOriginalExtension()) {
            case 'eln':
                return new Eln(
                    $this->requester,
                    $targetUser,
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
                    $this->requester,
                    $targetUser,
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
