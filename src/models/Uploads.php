<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function copy;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UploadParams;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\StorageFactory;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\UploadParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\MakeThumbnail;
use Elabftw\Traits\UploadTrait;
use ImagickException;
use League\Flysystem\UnableToRetrieveMetadata;
use PDO;
use RuntimeException;

/**
 * All about the file uploads
 */
class Uploads implements CrudInterface
{
    use UploadTrait;

    public const STATE_DELETED = 3;

    public const STATE_ARCHIVED = 2;

    /** @var int BIG_FILE_THRESHOLD size of a file in bytes above which we don't process it (50 Mb) */
    private const BIG_FILE_THRESHOLD = 50000000;

    private const STATE_NORMAL = 1;

    public array $uploadData = array();

    protected Db $Db;

    private string $hashAlgorithm = 'sha256';

    public function __construct(public AbstractEntity $Entity, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        if ($this->id !== null) {
            $this->readOne();
        }
    }

    /**
     * Main method for normal file upload
     */
    public function create(CreateUploadParamsInterface $params): int
    {
        $this->Entity->canOrExplode('write');

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
        $storageFs = (new StorageFactory($storage))->getStorage()->getFs();

        $tmpFilename = basename($params->getFilePath());
        $filesize = $sourceFs->filesize($tmpFilename);
        $hash = '';
        // we don't hash big files as this could take too much time/resources
        // same with thumbnails
        if ($filesize < self::BIG_FILE_THRESHOLD) {
            // read the file
            $fileContent = $sourceFs->read($tmpFilename);
            // get a hash sum
            $hash = $this->getHash($fileContent);
            // get a thumbnail
            // if the mimetype fails, do nothing
            // Imagick cannot open password protected PDFs, thumbnail generation will throw ImagickException
            try {
                $mime = $sourceFs->mimeType($tmpFilename);
                $MakeThumbnail = new MakeThumbnail($mime, $fileContent, $longName);
                if (!$storageFs->fileExists($MakeThumbnail->thumbFilename)) {
                    $thumbnailContent = $MakeThumbnail->makeThumb();
                    if ($thumbnailContent !== null) {
                        // save thumbnail
                        $storageFs->write($MakeThumbnail->thumbFilename, $thumbnailContent);
                    }
                }
            } catch (UnableToRetrieveMetadata | ImagickException $e) {
                // if mime type could not be read just ignore it and continue
                // if imagick/imagemagick causes problems ignore it and upload file without thumbnail
            }
        }
        // read the file as a stream so we can copy it
        $inputStream = $sourceFs->readStream($tmpFilename);

        $storageFs->createDirectory($folder);
        $storageFs->writeStream($longName, $inputStream);

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
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);
        $req->bindParam(':storage', $storage, PDO::PARAM_INT);
        $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $req->bindValue(':immutable', $params->getImmutable(), PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Create an upload from a string (binary png data or json string or mol file)
     * For mol file the code is actually in chemdoodle-uis-unpacked.js from chemdoodle-web-mini repository
     */
    public function createFromString(FileFromString $fileType, string $realName, string $content): int
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

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            return $this->readAll();
        }
        if ($params->getTarget() === 'uploadid') {
            $this->id = $this->getIdFromLongname($params->getContent());
        }
        return $this->readOne();
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

    /**
     * Read only the normal ones (not archived/deleted)
     */
    public function readAll(): array
    {
        $sql = 'SELECT uploads.*, CONCAT (users.firstname, " ", users.lastname) AS fullname
            FROM uploads LEFT JOIN users ON (uploads.userid = users.userid) WHERE item_id = :id AND type = :type AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindValue(':state', self::STATE_NORMAL, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function update(UploadParamsInterface $params): bool
    {
        $this->canWriteOrExplode();
        $sql = 'UPDATE uploads SET ' . $params->getTarget() . ' = :content WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Make a body check and then remove upload
     */
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        // check that the filename is not in the body. see #432
        if (strpos($this->Entity->entityData['body'], $this->uploadData['long_name'])) {
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
    public function destroyAll(): void
    {
        // this will include the archived/deleted ones
        $uploadArr = $this->readAll();

        foreach ($uploadArr as $upload) {
            $this->setId($upload['id']);
            $this->nuke();
        }
    }

    public function getStorageFromLongname(string $longname): int
    {
        $sql = 'SELECT storage FROM uploads WHERE long_name = :long_name LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':long_name', $longname, PDO::PARAM_STR);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function getIdFromLongname(string $longname): int
    {
        $sql = 'SELECT id FROM uploads WHERE long_name = :long_name LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':long_name', $longname, PDO::PARAM_STR);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Attached files are immutable (change history is kept), so the current
     * file gets its state changed to "archived" and a new one is added
     */
    public function replace(UploadParamsInterface $params): array
    {
        $this->canWriteOrExplode();
        // read the current one to get the comment
        $upload = $this->read(new ContentParams());
        $this->update(new UploadParams((string) self::STATE_ARCHIVED, 'state'));

        $file = $params->getFile();
        $newID = $this->create(new CreateUpload($file->getClientOriginalName(), $file->getPathname(), $upload['comment']));
        $this->setId($newID);

        return $this->uploadData;
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

    /**
     * This function will not remove the files but set them to "deleted" state
     * A manual purge must be made by sysadmin if they wish to really remove them.
     */
    private function nuke(): bool
    {
        if ($this->uploadData['immutable'] === 0) {
            return $this->update(new UploadParams((string) self::STATE_DELETED, 'state'));
        }
        return false;
    }

    /**
     * Generate the hash based on selected algorithm
     */
    private function getHash(string $content): string
    {
        return hash($this->hashAlgorithm, $content);
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
