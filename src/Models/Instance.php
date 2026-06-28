<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\Branding;
use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Override;
use PDO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;

use function explode;
use function in_array;
use function str_starts_with;
use function file_get_contents;
use function strlen;
use function strtolower;

/**
 * Instance level actions
 */
final class Instance extends AbstractRest
{
    private const int BRANDING_MAX_FILESIZE = 1048576; // 1 MiB

    private const array ALLOWED_BRANDING_CONTENT_TYPES = array(
        'image/svg+xml',
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    );

    public function __construct(private readonly Users $requester, private readonly Email $email, private bool $emailSendGrouped)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/';
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if (!in_array($action, array(Action::EmailBookers, Action::EmailTeam, Action::Update), true)) {
            $this->requester->isSysadminOrExplode();
        }
        return match ($action) {
            Action::AllowUntrusted => $this->Db->qToRowCount('UPDATE users SET allow_untrusted = 1'),
            Action::ClearLockedOutDevices => $this->Db->qToRowCount('DELETE FROM lockout_devices'),
            Action::Test => $this->email->testemailSend((string) $reqBody['email']),
            Action::Email => $this->email->massEmail(
                EmailTarget::from((string) $reqBody['target']),
                null,
                Filter::toPureString($reqBody['subject']),
                Filter::toPureString($reqBody['body']),
                new Address($this->requester->userData['email'], $this->requester->userData['fullname']),
                $this->emailSendGrouped,
            ),
            Action::EmailBookers => $this->email->notifyBookers(
                $this->requester,
                $reqBody['subject'],
                $reqBody['body'],
                new Items($this->requester, (int) $reqBody['entity_id']),
            ),
            Action::EmailTeam => $this->emailTeam($reqBody),
            Action::Update => $this->updateBranding($reqBody),
            default => throw new ImproperActionException('Invalid action parameter sent.'),
        };
    }

    private function emailTeam(array $reqBody): int
    {
        $target = (string) $reqBody['target'];
        // default to team
        $targetId = $this->requester->userData['team'];
        $targetType = EmailTarget::Team;
        if (str_starts_with($target, 'teamgroup')) {
            $targetId = (int) explode('_', $target)[1];
            $targetType = EmailTarget::TeamGroup;
        }
        $replyTo = new Address($this->requester->userData['email'], $this->requester->userData['fullname']);
        return $this->email->massEmail(
            $targetType,
            $targetId,
            $reqBody['subject'],
            $reqBody['body'],
            $replyTo,
            $this->emailSendGrouped,
        );
    }

    private function updateBranding(array $params): int
    {
        $branding = Branding::tryFrom((int) ($params['id'] ?? 0));

        if ($branding === null) {
            throw new ImproperActionException('Invalid branding id.');
        }

        $file = $params['file'] ?? null;

        if (!$file instanceof UploadedFile) {
            throw new ImproperActionException('Missing branding file.');
        }

        if (!$file->isValid()) {
            throw new ImproperActionException('Could not upload branding file.');
        }

        $contentType = strtolower($file->getClientMimeType());

        if (!in_array($contentType, self::ALLOWED_BRANDING_CONTENT_TYPES, true)) {
            throw new ImproperActionException('Unsupported branding file type.');
        }

        $filesize = $file->getSize();

        if ($filesize < 1 || $filesize > self::BRANDING_MAX_FILESIZE) {
            throw new ImproperActionException('Invalid branding file size.');
        }

        $data = file_get_contents($file->getPathname());

        if ($data === false || $data === '') {
            throw new ImproperActionException('Could not read branding file.');
        }

        $sql = 'INSERT INTO branding (id, content_type, data, filesize)
            VALUES (:id, :content_type, :data, :filesize)
            ON DUPLICATE KEY UPDATE
                content_type = VALUES(content_type),
                data = VALUES(data),
                filesize = VALUES(filesize),
                modified_at = CURRENT_TIMESTAMP';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $branding->value, PDO::PARAM_INT);
        $req->bindValue(':content_type', $contentType);
        $req->bindValue(':data', $data, PDO::PARAM_LOB);
        $req->bindValue(':filesize', strlen($data), PDO::PARAM_INT);
        $req->execute();

        return $branding->value;
    }
}
