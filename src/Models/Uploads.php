<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Controllers\DownloadController;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\CreateUploadFromS3;
use Elabftw\Elabftw\CreateUploadFromUploadedFile;
use Elabftw\Enums\AccessType;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\MakeThumbnailFactory;
use Elabftw\Hash\StreamHasher;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\ContentParams;
use Elabftw\Params\Guard;
use Elabftw\Params\UploadParams;
use Elabftw\Services\Check;
use ImagickException;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Override;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

use function mb_substr;
use function _;
use function array_column;
use function array_key_exists;
use function array_map;
use function is_array;
use function base64_decode;
use function basename;
use function dirname;
use function fclose;
use function fopen;
use function implode;
use function rewind;
use function sprintf;
use function str_replace;
use function stream_copy_to_stream;
use function stream_get_meta_data;
use function str_contains;
use function stream_get_contents;

/**
 * All about the file uploads
 */
final class Uploads extends AbstractRest
{
    // size of a file in bytes above which we don't process it (50 Mb)
    private const int BIG_FILE_THRESHOLD = 50000000;

    public array $uploadData = array();

    public function __construct(public AbstractEntity $Entity, public ?int $id = null)
    {
        parent::__construct();
        if ($this->id !== null) {
            $this->readOne();
        }
    }

    /**
     * Main method for normal file upload
     * @psalm-suppress UndefinedClass
     */
    public function create(CreateUploadParamsInterface $params, bool $isTimestamp = false, ?int $groupId = null): int
    {
        // by default we need write access to an entity to upload files
        $rw = AccessType::Write;
        // but timestamping/sign only needs read access
        if ($isTimestamp) {
            $rw = AccessType::Read;
        }
        $this->Entity->canOrExplode($rw);
        $this->assertGroupBelongsToEntity($groupId);
        $ordering = $this->getNextOrdering($groupId);

        // original file name
        $realName = $params->getFilename();

        $ext = $this->getExtensionOrExplode($realName);

        // name for the stored file, includes folder and extension (ab/ab34[...].ext)
        $someRandomString = Tools::getUuidv4();
        $folder = mb_substr($someRandomString, 0, 2);
        $longName = sprintf('%s/%s.%s', $folder, $someRandomString, $ext);

        // where our uploaded file lives
        $sourceFs = $params->getSourceFs();
        // where we want to store it
        $Config = Config::getConfig();
        $storage = (int) $Config->configArr['uploads_storage'];
        $storageFs = Storage::from($storage)->getStorage()->getFs();

        $tmpFilename = $params->getTmpFilePath();
        $filesize = $sourceFs->filesize($tmpFilename);
        // read the file as a stream
        $inputStream = $sourceFs->readStream($tmpFilename);

        // get metadata about the stream to see if it's seekable
        $meta = stream_get_meta_data($inputStream);
        if (empty($meta['seekable'])) {
            // make a seekable temp stream
            $tmp = fopen('php://temp', 'w+b');
            if ($tmp === false) {
                throw new RuntimeException('Could not create temporary seekable stream.');
            }

            stream_copy_to_stream($inputStream, $tmp);
            fclose($inputStream);
            $inputStream = $tmp;
        }

        $isRewind = rewind($inputStream);
        if ($isRewind === false) {
            throw new RuntimeException('Could not rewind stream.');
        }

        // Inspect the content instead of relying on the source backend's metadata.
        $sample = stream_get_contents($inputStream, 64 * 1024);
        if ($sample === false) {
            throw new RuntimeException('Could not read stream for MIME type detection.');
        }

        if (rewind($inputStream) === false) {
            throw new RuntimeException('Could not rewind stream after MIME type detection.');
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeType($realName, $sample)
            ?? 'application/octet-stream';

        // keep a size limit for thumbnail generation
        if ($filesize < self::BIG_FILE_THRESHOLD) {
            // get a thumbnail
            // Imagick cannot open password protected PDFs, thumbnail generation will throw ImagickException
            try {
                MakeThumbnailFactory::getMaker(
                    $mimeType,
                    $inputStream,
                    $longName,
                    $storageFs,
                )->saveThumb();
            } catch (UnableToRetrieveMetadata | ImagickException $e) {
                // if mime type could not be read just ignore it and continue
                // if imagick/imagemagick causes problems ignore it and upload file without thumbnail
                App::getDefaultLogger()->warning(sprintf('Error during thumbnail generation: %s', $e->getMessage()));
            }
        }

        // actual writing of the file in its destination, after rewinding file
        $isRewind = rewind($inputStream);
        if ($isRewind === false) {
            throw new RuntimeException('Could not rewind stream.');
        }

        $storageFs->createDirectory($folder);
        $hasher = new StreamHasher($inputStream);
        $uploadStream = $hasher->getResource();

        $storageFs->writeStream($longName, $uploadStream, array('mimetype' => $mimeType));

        $hash = $hasher->getHash();

        fclose($uploadStream);
        fclose($inputStream);


        $this->Entity->touch();

        // final sql
        $sql = 'INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            group_id,
            ordering,
            userid,
            type,
            hash,
            hash_algorithm,
            state,
            storage,
            filesize,
            immutable
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :group_id,
            :ordering,
            :userid,
            :type,
            :hash,
            :hash_algorithm,
            :state,
            :storage,
            :filesize,
            :immutable
        )';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        $req->bindValue(':comment', $params->getComment());
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $req->bindValue(':hash', $hash);
        $req->bindValue(':hash_algorithm', $hasher->getAlgo());
        $req->bindValue(':state', $params->getState()->value, PDO::PARAM_INT);
        $req->bindParam(':storage', $storage, PDO::PARAM_INT);
        $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $req->bindValue(':immutable', $params->getImmutable(), PDO::PARAM_INT);
        $this->Db->execute($req);
        $uploadId = $this->Db->lastInsertId();

        $Changelog = new Changelog($this->Entity);
        $params = new ContentParams('uploads', sprintf('Added upload "%s" with id: %d', $realName, $uploadId));
        $Changelog->create($params);

        return $uploadId;
    }

    // entity is target entity
    public function duplicate(AbstractEntity $entity): void
    {
        $groupMap = new UploadGroups($this->Entity)->duplicate($entity);
        $uploads = $this->selectAll(array(State::Normal));
        $body = $entity->entityData['body'];
        foreach ($uploads as $upload) {
            $param = $this->makeCreateUploadParam($upload);
            $groupId = $upload['group_id'] === null ? null : ($groupMap[(int) $upload['group_id']] ?? null);
            $id = $entity->Uploads->create($param, groupId: $groupId);
            $fresh = new self($entity, $id);
            // replace links in body with the new long_name. Skip if body is null
            if ($body === null) {
                continue;
            }
            $body = str_replace($upload['long_name'], $fresh->uploadData['long_name'], $body);
        }
        if ($body !== null && $body !== $entity->entityData['body']) {
            $entity->patch(Action::Update, array('body' => $body));
        }
    }

    public function duplicateOne(): int
    {
        $this->canWriteOrExplode();
        $param = $this->makeCreateUploadParam($this->uploadData);
        $groupId = $this->uploadData['group_id'] === null ? null : (int) $this->uploadData['group_id'];
        return $this->Entity->Uploads->create($param, groupId: $groupId);
    }

    /**
     * Read from current id
     */
    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid) WHERE id = :id AND item_id = :item_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $this->Db->execute($req);
        $this->uploadData = $this->Db->fetch($req);
        return $this->uploadData;
    }

    public function readFilesizeSum(): int
    {
        $sql = 'SELECT SUM(filesize) FROM uploads';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Read an upload in binary format, so the actual file uploaded
     */
    public function readBinary(): Response
    {
        $storageFs = Storage::from($this->uploadData['storage'])->getStorage()->getFs();

        $DownloadController = new DownloadController(
            $storageFs,
            $this->uploadData['long_name'],
            $this->uploadData['real_name'],
            forceDownload: false,
        );
        return $DownloadController->getResponse();
    }

    public function selectAll(?array $states = null): array
    {
        // if no states array is provided, select all
        $states ??= array(State::Normal, State::Archived, State::Deleted);
        $statesSql = sprintf(' AND uploads.state IN (%s)', implode(', ', array_map(fn($state) => $state->value, $states)));
        $hasGroups = (new UploadGroups($this->Entity))->readAll() !== array();
        $orderSql = $hasGroups
            ? '(uploads.group_id IS NOT NULL) ASC, upload_groups.ordering ASC, upload_groups.id ASC, uploads.ordering ASC, uploads.id ASC'
            : 'uploads.created_at DESC';
        $sql = sprintf(
            'SELECT uploads.*, MAX(uploads.id) OVER () AS latest_upload_id, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads
            LEFT JOIN users ON uploads.userid = users.userid
            LEFT JOIN upload_groups ON upload_groups.id = uploads.group_id
                AND upload_groups.entity_id = uploads.item_id
                AND upload_groups.entity_type = uploads.type
            WHERE uploads.item_id = :id AND uploads.type = :type %s ORDER BY %s',
            $statesSql,
            $orderSql,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Public api for GET all uploads for the current entity
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        return $this->selectAll($queryParams->getStates());
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($action === Action::Update && $this->id === null && array_key_exists('grouped_ordering', $params)) {
            $this->Entity->canOrExplode(AccessType::Write);
            if (!is_array($params['grouped_ordering'])) {
                throw new ImproperActionException(_('Invalid grouped uploads ordering.'));
            }
            $this->updateGroupedOrdering($params['grouped_ordering']);
            $this->Entity->touch();
            new Changelog($this->Entity)->create(new ContentParams('uploads', Action::Update->value));
            return $this->readAll();
        }
        if ($this->id === null) {
            throw new ImproperActionException(_('An upload id is required for this update.'));
        }
        $this->canWriteOrExplode();
        $this->Entity->touch();
        if ($action === Action::Archive) {
            return $this->archive();
        }
        unset($params['action']);
        foreach ($params as $key => $value) {
            $this->update(new UploadParams($key, $value));
        }
        return $this->readOne();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->Entity->touch();
        $realName = ($action === Action::Replace || $action === Action::Create)
            ? Guard::getNonEmptyStringValueOfRequiredParam('real_name', $reqBody)
            : ($this->uploadData['real_name']
                ?? Guard::getNonEmptyStringValueOfRequiredParam('real_name', $reqBody));
        $groupId = ($reqBody['group_id'] ?? null) === null || ($reqBody['group_id'] ?? '') === ''
            ? null
            : (int) $reqBody['group_id'];
        return match ($action) {
            Action::Create => $this->create(
                new CreateUploadFromUploadedFile(new UploadedFile($reqBody['filePath'], $realName), $reqBody['comment']),
                groupId: $groupId,
            ),
            Action::CreateFromString => (
                function () use ($reqBody, $realName, $groupId) {
                    $fileType = FileFromString::tryFrom($reqBody['file_type']);
                    if ($fileType === null) {
                        throw new ImproperActionException(sprintf('Invalid file_type parameter. Valid values are: %s.', FileFromString::toCsList()));
                    }
                    if (empty($reqBody['content'])) {
                        throw new ImproperActionException('Cannot create file from string with empty content!');
                    }
                    return $this->createFromString($fileType, $realName, $reqBody['content'], groupId: $groupId);
                }
            )(),
            Action::Duplicate => $this->duplicateOne(),
            Action::Replace => $this->replace(new CreateUploadFromUploadedFile(
                new UploadedFile($reqBody['filePath'], $realName),
                $this->uploadData['comment']
            )),
            default => throw new ImproperActionException('Invalid action for upload creation.'),
        };
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/uploads/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->canWriteOrExplode();
        $this->Entity->touch();
        $this->checkUploadIsNotReferenced();
        return $this->nuke();
    }

    public function setId(int $id): void
    {
        if (Check::id($id) === false) {
            throw new ImproperActionException('The id parameter is not valid!');
        }
        $this->id = $id;
        // load it
        $this->readOne();
    }

    /**
     * Soft delete all uploaded files for an entity
     */
    public function destroyAll(): bool
    {
        $sql = 'UPDATE uploads SET state = :state_deleted WHERE item_id = :id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->Entity->id);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $req->bindValue(':state_deleted', State::Deleted->value);
        return $this->Db->execute($req);
    }

    /**
     * Restore all uploaded files to normal state for an entity (excluding archived to keep consistency)
     */
    public function restoreAll(): bool
    {
        $sql = 'UPDATE uploads SET state = :state_normal WHERE item_id = :id AND type = :type AND state != :state_archived';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->Entity->id);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $req->bindValue(':state_normal', State::Normal->value);
        $req->bindValue(':state_archived', State::Archived->value);
        return $this->Db->execute($req);
    }

    public function getStorageFromLongname(string $longname): int
    {
        $sql = 'SELECT storage FROM uploads WHERE long_name = :long_name LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':long_name', $longname);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Create an upload from a string (binary png data or json string or mol file)
     */
    public function createFromString(FileFromString $fileType, string $realName, string $content, State $state = State::Normal, ?int $groupId = null): int
    {
        // a png file will be received as dataurl, so we need to convert it to binary before saving it
        if ($fileType === FileFromString::Png) {
            $content = $this->pngDataUrlToBinary($content);
        }

        // add file extension if it wasn't provided
        if (Tools::getExt($realName) === 'unknown') {
            $realName .= '.' . $fileType->value;
        }
        // create a temporary file so we can upload it using create()
        $tmpFilePath = FsTools::getCacheFile();
        $tmpFilePathFs = FsTools::getFs(dirname($tmpFilePath));
        $tmpFilePathFs->write(basename($tmpFilePath), $content);

        return $this->create(new CreateUpload($realName, $tmpFilePath, state: $state), groupId: $groupId);
    }

    /**
     * Attached files are immutable (change history is kept), so the current
     * file gets its state changed to "archived" and a new one is added
     */
    public function replace(CreateUploadParamsInterface $params): int
    {
        $groupId = $this->uploadData['group_id'] === null ? null : (int) $this->uploadData['group_id'];
        $this->archive();
        return $this->create($params, groupId: $groupId);
    }

    // transfer ownership of all uploaded files for an entity, except immutable ones
    public function transferOwnership(int $userid): bool
    {
        $sql = 'UPDATE uploads SET userid = :userid WHERE item_id = :item_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        return $this->Db->execute($req);
    }

    // check that the filename is not in the body. see #432
    private function checkUploadIsNotReferenced(): void
    {
        $body = $this->Entity->entityData['body'] ?? '';
        if (str_contains($body, $this->uploadData['long_name'])) {
            throw new ImproperActionException(_('Please make sure to remove any reference to this file in the body!'));
        }
    }

    private function makeCreateUploadParam(array $upload): CreateUploadParamsInterface
    {
        if ($upload['storage'] === Storage::LOCAL->value) {
            $prefix = Storage::LOCAL->getStorage()->getPath() . '/';
            return new CreateUpload(
                realName: $upload['real_name'],
                filePath: $prefix . $upload['long_name'],
                comment: $upload['comment'],
                state: State::from($upload['state']),
            );
        }
        return new CreateUploadFromS3(
            realName: $upload['real_name'],
            filePath: $upload['long_name'],
            comment: $upload['comment'],
            state: State::from($upload['state']),
        );
    }

    private function update(UploadParams $params): bool
    {
        $sql = 'UPDATE uploads SET ' . $params->getColumn() . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $Changelog = new Changelog($this->Entity);
        $contentParams = new ContentParams(
            'uploads',
            sprintf(
                'Changed upload "%s" with id %d: updated %s to %s',
                $this->uploadData['real_name'],
                $this->id,
                $params->getColumn(),
                $params->getContent(),
            ),
        );
        $Changelog->create($contentParams);
        return $this->Db->execute($req);
    }

    private function updateGroupedOrdering(array $groups): void
    {
        $uploads = array_column($this->selectAll(array(State::Normal, State::Archived)), null, 'id');
        $seen = array();
        $sql = 'UPDATE uploads SET group_id = :group_id, ordering = :ordering WHERE id = :id AND item_id = :entity_id AND type = :entity_type';
        $req = $this->Db->prepare($sql);
        foreach ($groups as $group) {
            if (!is_array($group) || !array_key_exists('upload_ids', $group) || !is_array($group['upload_ids'])) {
                throw new ImproperActionException(_('Invalid grouped uploads ordering.'));
            }
            $rawGroupId = $group['group_id'] ?? null;
            $groupId = $rawGroupId === null || $rawGroupId === '' ? null : (int) $rawGroupId;
            $this->assertGroupBelongsToEntity($groupId);

            foreach ($group['upload_ids'] as $ordering => $rawUploadId) {
                $uploadId = (int) $rawUploadId;
                if (!array_key_exists($uploadId, $uploads) || isset($seen[$uploadId])) {
                    throw new ImproperActionException(_('Cannot reorder an upload that does not belong to this entity.'));
                }
                $seen[$uploadId] = true;
                $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $req->bindValue(':ordering', $ordering, PDO::PARAM_INT);
                $req->bindValue(':id', $uploadId, PDO::PARAM_INT);
                $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
                $req->bindValue(':entity_type', $this->Entity->entityType->value);
                $this->Db->execute($req);
            }
        }
    }

    private function assertGroupBelongsToEntity(?int $groupId): void
    {
        if ($groupId === null) {
            return;
        }
        if ($groupId < 1) {
            throw new ImproperActionException(_('Invalid upload group.'));
        }
        new UploadGroups($this->Entity, $groupId)->readOne();
    }

    private function getNextOrdering(?int $groupId): int
    {
        $sql = 'SELECT COALESCE(MAX(ordering), -1) + 1 FROM uploads
            WHERE item_id = :entity_id AND type = :entity_type AND group_id <=> :group_id AND state != :state_deleted';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->value);
        $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindValue(':state_deleted', State::Deleted->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Transform a png data url into its binary form
     */
    private function pngDataUrlToBinary(string $content): string
    {
        $content = str_replace(array('data:image/png;base64,', ' '), array('', '+'), $content);
        $content = base64_decode($content, true);
        if ($content === false) {
            throw new RuntimeException('Could not decode content!');
        }
        return $content;
    }

    private function canWriteOrExplode(): void
    {
        if ($this->uploadData['immutable'] === 1) {
            throw new ForbiddenException('User tried to edit an immutable upload.');
        }
        $this->Entity->canOrExplode(AccessType::Write);
    }

    private function archive(): array
    {
        $this->canWriteOrExplode();
        $this->checkUploadIsNotReferenced();
        $targetState = State::Archived->value;
        // if already archived, unarchive
        if ($this->uploadData['state'] === State::Archived->value) {
            $targetState = State::Normal->value;
        }
        $this->update(new UploadParams('state', (string) $targetState));
        return $this->readOne();
    }

    /**
     * This function will not remove the files but set them to "deleted" state
     * A manual purge must be made by sysadmin if they wish to really remove them.
     */
    private function nuke(): bool
    {
        if ($this->uploadData['immutable'] === 0) {
            return $this->update(new UploadParams('state', (string) State::Deleted->value));
        }
        return false;
    }

    /**
     * Check if extension is allowed for upload
     *
     * @param string $realName The name of the file
     */
    private function getExtensionOrExplode(string $realName): string
    {
        $ext = Tools::getExt($realName);
        if ($ext === 'php') {
            throw new ImproperActionException('PHP files are forbidden!');
        }
        return $ext;
    }
}
