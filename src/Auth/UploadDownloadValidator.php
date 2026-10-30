<?php

/**
 * @copyright 2026 Nicolas CARPi / Deltablot
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Permissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Filter;
use PDO;

use function basename;
use function hash_equals;
use function mb_substr;
use function str_ends_with;
use function substr;
use function strtolower;

final class UploadDownloadValidator
{
    public function validate(?Users $requester, string $longName, ?int $storage = null, string $accessKey = ''): void
    {
        // Match DownloadController's path normalization before resolving the parent.
        $longName = Filter::forFilesystem(basename($longName));
        if (str_ends_with(strtolower($longName), '_th.jpg')) {
            $longName = substr($longName, 0, -7);
        }

        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT type, item_id, storage, state FROM uploads
            WHERE long_name IN (:long_name, :legacy_long_name) LIMIT 1');
        $req->bindValue(':long_name', mb_substr($longName, 0, 2) . '/' . $longName);
        $req->bindValue(':legacy_long_name', $longName);
        $Db->execute($req);
        $upload = $req->fetch();
        if ($upload === false) {
            // Exports are authorized by their caller and have no upload record.
            if ($requester === null) {
                return;
            }
            throw new ResourceNotFoundException();
        }
        if ($upload['state'] === State::Deleted->value || ($storage !== null && $upload['storage'] !== $storage)) {
            throw new ResourceNotFoundException();
        }
        // An upload always needs a requester, even when the caller expected an export.
        if ($requester === null) {
            throw new ForbiddenException();
        }
        $type = EntityType::tryFrom($upload['type']) ?? throw new ResourceNotFoundException();
        if (empty($upload['item_id'])) {
            throw new ResourceNotFoundException();
        }

        // Avoid loading the body and every subresource for each thumbnail request.
        $req = $Db->prepare('SELECT userid, team, canread_base, canread, canwrite_base, canwrite, access_key
            FROM ' . $type->value . ' WHERE id = :id AND state != :deleted');
        $req->bindValue(':id', $upload['item_id'], PDO::PARAM_INT);
        $req->bindValue(':deleted', State::Deleted->value, PDO::PARAM_INT);
        $Db->execute($req);
        $entity = $Db->fetch($req);

        if ($accessKey !== '' && !empty($entity['access_key']) && hash_equals($entity['access_key'], $accessKey)) {
            return;
        }

        if (!new Permissions($requester, $entity)->forEntity()->read) {
            throw new ForbiddenException();
        }
    }
}
