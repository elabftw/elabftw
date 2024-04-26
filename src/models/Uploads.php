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
use Elabftw\Elabftw\CreateImmutableArchivedUpload;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\CreateUploadFromS3;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UploadParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\MakeThumbnailFactory;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Check;
use Elabftw\Traits\UploadTrait;
use ImagickException;
use League\Flysystem\UnableToRetrieveMetadata;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function hash_file;

/**
 * All about the file uploads
 */
class Uploads implements RestInterface
{
    use UploadTrait;

    public const string HASH_ALGORITHM = 'sha256';

    // size of a file in bytes above which we don't process it (50 Mb)
    private const int BIG_FILE_THRESHOLD = 50000000;

    public array $uploadData = array();

    public bool $includeArchived = false;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        if ($this->id !== null) {
            $this->readOne();
        }
    }

    /**
     * Main method for normal file upload
     * @psalm-suppress UndefinedClass
     */
    public function create(CreateUploadParamsInterface $params): int
    {
        // by default we need write access to an entity to upload files
        $rw = 'write';
        // but timestamping/sign only needs read access
        if ($params instanceof CreateImmutableArchivedUpload) {
            $rw = 'read';
        }
        $this->Entity->canOrExplode($rw);

        // original file name
        $realName = $params->getFilename();
        $ext = $this->getExtensionOrExplode($realName);

        // name for the stored file, includes folder and extension (ab/ab34[...].ext)
        $longName = $this->getLongName() . '.' . $ext;
        $folder = substr($longName, 0, 2);

        // where our uploaded file lives
        $sourceFs = $params->getSourceFs();
        // where we want to store it
        $Config = Config::getConfig();
        $storage = (int) $Config->configArr['uploads_storage'];
        $storageFs = Storage::from($storage)->getStorage()->getFs();

        $tmpFilename = $params->getTmpFilePath();
        $filesize = $sourceFs->filesize($tmpFilename);
        $hash = '';
        // we don't hash big files as this could take too much time/resources
        // same with thumbnails
        if ($filesize < self::BIG_FILE_THRESHOLD) {
            // get a hash sum
            $hash = hash_file(self::HASH_ALGORITHM, $params->getFilePath());
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
        $req->bindParam(':type', $this->Entity->type);
        $req->bindParam(':hash', $hash);
        $req->bindValue(':hash_algorithm', self::HASH_ALGORITHM);
        $req->bindValue(':state', $params->getState()->value, PDO::PARAM_INT);
        $req->bindParam(':storage', $storage, PDO::PARAM_INT);
        $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $req->bindValue(':immutable', $params->getImmutable(), PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function duplicate(AbstractEntity $entity): void
    {
        $uploads = $this->readAll();
        foreach ($uploads as $upload) {
            if ($upload['storage'] === Storage::LOCAL->value) {
                $prefix = '/elabftw/uploads/';
                $param = new CreateUpload($upload['real_name'], $prefix . $upload['long_name'], $upload['comment']);
            } else {
                $param = new CreateUploadFromS3($upload['real_name'], $upload['long_name'], $upload['comment']);
            }
            $entity->Uploads->create($param);
        }
    }

    /**
     * Read from current id
     */
    public function readOne(): array
    {
        $sql = 'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
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
            true,
        );
        return $DownloadController->getResponse();
    }

    /**
     * Read only the normal ones (not archived/deleted)
     */
    public function readAll(): array
    {
        if ($this->includeArchived) {
            return $this->readNormalAndArchived();
        }
        $sql = 'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid) WHERE item_id = :id AND type = :type AND state = :state ORDER BY created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

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

    public function postAction(Action $action, array $reqBody): int
    {
        $this->Entity->touch();
        if ($this->id !== null) {
            $action = Action::Replace;
        }
        return match ($action) {
            Action::Create => $this->create(new CreateUpload($reqBody['real_name'], $reqBody['filePath'], $reqBody['comment'])),
            Action::CreateFromString => $this->createFromString(FileFromString::from($reqBody['file_type']), $reqBody['real_name'], $reqBody['content']),
            Action::Replace => $this->replace(new CreateUpload($reqBody['real_name'], $reqBody['filePath'])),
            default => throw new ImproperActionException('Invalid action for upload creation.'),
        };
    }

    public function getPage(): string
    {
        return sprintf('api/v2/%s/%d/uploads/', $this->Entity->page, $this->Entity->id ?? 0);
    }

    /**
     * Make a body check and then remove upload
     */
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
     * Delete all uploaded files for an entity
     */
    public function destroyAll(): bool
    {
        // this will include the archived/deleted ones
        $uploadArr = $this->readAll();

        foreach ($uploadArr as $upload) {
            $this->setId($upload['id']);
            $this->nuke();
        }
        return true;
    }

    public function getStorageFromLongname(string $longname): int
    {
        $sql = 'SELECT storage FROM uploads WHERE long_name = :long_name LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':long_name', $longname, PDO::PARAM_STR);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function update(UploadParams $params): bool
    {
        $sql = 'UPDATE uploads SET ' . $params->getColumn() . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function readNormalAndArchived(): array
    {
        $sql = 'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid) WHERE item_id = :id AND type = :type AND (state = :normal OR state = :archived) ORDER BY uploads.created_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindValue(':normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();

    }

    /**
     * Attached files are immutable (change history is kept), so the current
     * file gets its state changed to "archived" and a new one is added
     */
    private function replace(CreateUpload $params): int
    {
        // read the current one to get the comment, and at the same time archive it
        $upload = $this->archive();

        return $this->create(new CreateUpload($params->getFilename(), $params->getFilePath(), $upload['comment']));
    }

    /**
     * Create an upload from a string (binary png data or json string or mol file)
     * For mol file the code is actually in chemdoodle-uis-unpacked.js from chemdoodle-web-mini repository
     */
    private function createFromString(FileFromString $fileType, string $realName, string $content): int
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

        return $this->create(new CreateUpload($realName, $tmpFilePath));
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
