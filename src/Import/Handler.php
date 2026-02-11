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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Users\Users;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        // if we come from api, the controller will
        // use getInt to get owner, if it's unset it will be 0 and not null
        // but if we call postAction from php code (like in tests) it can be null
        $reqBody['owner'] ??= $this->requester->userid;
        $owner = ($reqBody['owner'] === 0 ? $this->requester->userid : $reqBody['owner']) ?? throw new ImproperActionException('Could not find owner!');
        if ($owner !== $this->requester->userid && $this->requester->isAdminOf($owner)) {
            $this->requester = new Users($owner, $this->requester->team);
        }
        $canreadBase = BasePermissions::tryFrom((int) ($reqBody['canread_base'] ?? BasePermissions::Team->value)) ?? BasePermissions::Team;
        $canwriteBase = BasePermissions::tryFrom((int) ($reqBody['canwrite_base'] ?? BasePermissions::User->value)) ?? BasePermissions::User;
        switch ($reqBody['file']->getClientOriginalExtension()) {
            case 'eln':
                return new Eln(
                    $this->requester,
                    $reqBody['file'],
                    Storage::CACHE->getStorage()->getFs(),
                    $this->logger,
                    EntityType::tryFrom((string) $reqBody['entity_type']), // can be null
                    category: (int) $reqBody['category'],
                    canreadBase: $canreadBase,
                    canwriteBase: $canwriteBase,
                );
            case 'csv':
                return new Csv(
                    $this->requester,
                    $reqBody['file'],
                    logger: $this->logger,
                    entityType: EntityType::tryFrom((string) $reqBody['entity_type']) ?? EntityType::Items,
                    category: (int) $reqBody['category'],
                    canreadBase: $canreadBase,
                    canwriteBase: $canwriteBase,
                );
            default:
                throw new ImproperActionException(sprintf(
                    _('Error: invalid file extension for import. Allowed extensions: %s.'),
                    implode(', ', self::ALLOWED_EXTENSIONS)
                ));
        }
    }
}
