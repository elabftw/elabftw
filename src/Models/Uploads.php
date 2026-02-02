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
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\CreateUploadFromS3;
use Elabftw\Elabftw\CreateUploadFromUploadedFile;
use Elabftw\Hash\ExistingHash;
use Elabftw\Elabftw\FsTools;
use Elabftw\Hash\StringHash;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\MakeThumbnailFactory;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\UploadParams;
use Elabftw\Services\Check;
use ImagickException;
use League\Flysystem\UnableToRetrieveMetadata;
use Override;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

use function mb_substr;

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
    public function create(CreateUploadParamsInterface $params, bool $isTimestamp = false): int
    {
        // by default we need write access to an entity to upload files
        $rw = 'write';
        // but timestamping/sign only needs read access
        if ($isTimestamp) {
            $rw = 'read';
        }
        $this->Entity->canOrExplode($rw);

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
        // we don't hash big files as this could take too much time/resources
        // same with thumbnails
        // TODO add the filesize check inside the makethumnailclass like we did for hasher
        if ($filesize < self::BIG_FILE_THRESHOLD) {
            // get a thumbnail
            // Imagick cannot open password protected PDFs, thumbnail generation will throw ImagickException
            try {
                MakeThumbnailFactory::getMaker(
                    $sourceFs->mimeType($tmpFilename),
                    $params->getFilePath(),
                    $longName,
                    $storageFs,
                )->saveThumb();
            } catch (UnableToRetrieveMetadata | ImagickException) {
                // if mime type could not be read just ignore it and continue
                // if imagick/imagemagick causes problems ignore it and upload file without thumbnail
            }
        }
        // read the file as a stream so we can copy it
        $inputStream = $sourceFs->readStream($tmpFilename);

        $storageFs->createDirectory($folder);
        $storageFs->writeStream($longName, $inputStream);

        $this->Entity->touch();

        // final sql
        $sql = 'INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
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
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        $req->bindValue(':hash', $params->getHasher()->getHash());
        $req->bindValue(':hash_algorithm', $params->getHasher()->getAlgo());
        $req->bindValue(':state', $params->getState()->value, PDO::PARAM_INT);
        $req->bindParam(':storage', $storage, PDO::PARAM_INT);
        $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $req->bindValue(':immutable', $params->getImmutable(), PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    // entity is target entity
    public function duplicate(AbstractEntity $entity): void
    {
        $uploads = $this->selectAll();
        foreach ($uploads as $upload) {
            if ($upload['storage'] === Storage::LOCAL->value) {
                $prefix = '/elabftw/uploads/';
                $param = new CreateUpload($upload['real_name'], $prefix . $upload['long_name'], new ExistingHash($upload['hash']), $upload['comment']);
            } else {
                $param = new CreateUploadFromS3($upload['real_name'], $upload['long_name'], new ExistingHash($upload['hash']), $upload['comment']);
            }
            $id = $entity->Uploads->create($param);
            $fresh = new self($entity, $id);
            // replace links in body with the new long_name
            // don't bother if body is null
            if ($entity->entityData['body'] === null) {
                continue;
            }
            $newBody = str_replace($upload['long_name'], $fresh->uploadData['long_name'], $entity->entityData['body']);
            $entity->patch(Action::Update, array('body' => $newBody));
        }
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
        $sql = sprintf(
            'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid)
            WHERE item_id = :id AND type = :type %s ORDER BY created_at DESC',
            $statesSql
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
        if ($this->id !== null) {
            $action = Action::Replace;
        }
        if (empty($reqBody['real_name'])) {
            throw new ImproperActionException('Cannot create an upload with an empty real_name value.');
        }
        return match ($action) {
            Action::Create => $this->create(
                new CreateUploadFromUploadedFile(new UploadedFile($reqBody['filePath'], $reqBody['real_name']), $reqBody['comment'])
            ),
            Action::CreateFromString => (
                function () use ($reqBody) {
                    $fileType = FileFromString::tryFrom($reqBody['file_type']);
                    if ($fileType === null) {
                        throw new ImproperActionException(sprintf('Invalid file_type parameter. Valid values are: %s.', FileFromString::toCsList()));
                    }
                    if (empty($reqBody['content'])) {
                        throw new ImproperActionException('Cannot create file from string with empty content!');
                    }
                    return $this->createFromString($fileType, $reqBody['real_name'], $reqBody['content']);
                }
            )(),
            Action::Replace => $this->replace(new CreateUploadFromUploadedFile(
                new UploadedFile($reqBody['filePath'], $reqBody['real_name'], $this->uploadData['comment'])
            )),
            default => throw new ImproperActionException('Invalid action for upload creation.'),
        };
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/uploads/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    /**
     * Make a body check and then remove upload
     */
    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $this->Entity->touch();
        // check that the filename is not in the body. see #432
        if (strpos($this->Entity->entityData['body'] ?? '', $this->uploadData['long_name'])) {
            throw new ImproperActionException(_('Please make sure to remove any reference to this file in the body!'));
        }
        return $this->nuke();
    }

    public function setId(int $id): void
    {
        if (Check::id($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
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
    public function createFromString(FileFromString $fileType, string $realName, string $content, State $state = State::Normal): int
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

        return $this->create(new CreateUpload($realName, $tmpFilePath, state: $state, hasher: new StringHash($content)));
    }

    /**
     * Attached files are immutable (change history is kept), so the current
     * file gets its state changed to "archived" and a new one is added
     */
    public function replace(CreateUploadParamsInterface $params): int
    {
        $this->archive();
        return $this->create($params);
    }

    // transfer ownership of all uploaded files for an entity, except immutable ones
    public function transferOwnership(int $userid): void
    {
        $uploadArr = $this->selectAll();
        foreach ($uploadArr as $upload) {
            if ($upload['immutable'] === 1) {
                continue;
            }
            $this->setId($upload['id']);
            $this->patch(Action::Update, array('userid' => $userid));
        }
    }

    private function update(UploadParams $params): bool
    {
        $sql = 'UPDATE uploads SET ' . $params->getColumn() . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
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
            throw new IllegalActionException('User tried to edit an immutable upload.');
        }
        $this->Entity->canOrExplode('write');
    }

    private function archive(): array
    {
        $this->canWriteOrExplode();
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
