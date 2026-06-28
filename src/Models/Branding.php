<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\Branding as BrandingEnum;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Override;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

use function in_array;
use function file_get_contents;
use function strlen;
use function strtolower;
use function dirname;
use function sprintf;

/**
 * Operations on branding table
 */
final class Branding extends AbstractRest
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

    public function __construct(private readonly bool $canwrite, private readonly ?int $id = null)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/branding/';
    }

    #[Override]
    public function readOne(): array
    {
        throw new ImproperActionException("Use format=binary on this endpoint. No\u{a0}JSON response available.");
    }

    public function readBinary(): Response
    {
        $branding = $this->selectOne();
        return new Response($branding['data'], Response::HTTP_OK, array(
            'Content-Type' => $branding['content_type'],
            'Content-Length' => (string) $branding['filesize'],
            'Cache-Control' => 'public, max-age=3600',
        ));
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Update => $this->update($reqBody),
            default => throw new ImproperActionException('Invalid action parameter sent.'),
        };
    }

    public function populate(): void
    {
        $branding = array(
            1 => 'logo-header.svg',
            2 => 'logo-light.svg',
            3 => 'logo-dark.svg',
            4 => 'favicon.svg',
        );

        $sql = 'INSERT INTO branding (id, content_type, data, filesize)
            VALUES (:id, :content_type, :data, :filesize)
            ON DUPLICATE KEY UPDATE
                content_type = VALUES(content_type),
                data = VALUES(data),
                filesize = VALUES(filesize),
                modified_at = CURRENT_TIMESTAMP';

        $req = $this->Db->prepare($sql);

        foreach ($branding as $id => $filename) {
            $path = dirname(__DIR__, 2) . '/web/assets/images/' . $filename;
            $data = file_get_contents($path);

            if ($data === false) {
                throw new RuntimeException(sprintf('Could not read branding file: %s', $path));
            }

            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $req->bindValue(':content_type', 'image/svg+xml');
            $req->bindValue(':data', $data, PDO::PARAM_LOB);
            $req->bindValue(':filesize', strlen($data), PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    private function selectOne(): array
    {
        $branding = BrandingEnum::tryFrom($this->id ?? 0) ?? throw new ImproperActionException('Invalid branding id.');

        $sql = 'SELECT id, content_type, data, filesize FROM branding WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $branding->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    private function update(array $params): int
    {
        if (!$this->canwrite) {
            throw new IllegalActionException();
        }
        $branding = BrandingEnum::tryFrom($this->id ?? 0) ?? throw new ImproperActionException('Invalid branding id.');

        $file = $params['file'] ?? null;

        if (!$file instanceof UploadedFile) {
            throw new ImproperActionException('Missing branding file.');
        }

        if (!$file->isValid()) {
            throw new ImproperActionException('Could not upload branding file.');
        }

        $contentType = strtolower($file->getMimeType() ?? 'unknown');

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
        $this->Db->execute($req);

        return $branding->value;
    }
}
