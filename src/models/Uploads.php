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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\UploadParamsInterface;
use Elabftw\Services\MakeThumbnail;
use Elabftw\Services\StorageFactory;
use Elabftw\Traits\SetIdTrait;
use Elabftw\Traits\UploadTrait;
use ImagickException;
use function in_array;
use League\Flysystem\UnableToRetrieveMetadata;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the file uploads
 */
class Uploads implements CrudInterface
{
    use UploadTrait;
    use SetIdTrait;

    public const STATE_DELETED = 3;

    /** @var int BIG_FILE_THRESHOLD size of a file in bytes above which we don't process it (50 Mb) */
    private const BIG_FILE_THRESHOLD = 50000000;

    private const STATE_NORMAL = 1;

    private const STATE_ARCHIVED = 2;

    protected Db $Db;

    private string $hashAlgorithm = 'sha256';

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
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
        $id = $this->dbInsert($realName, $longName, $hash, $filesize, $storage, $params->getComment());

        // TODO useful?
        $sourceFs->delete($params->getFilePath());

        return $id;
    }

    /**
     * Create an upload from a string, from Chemdoodle or Doodle
     */
    public function createFromString(string $fileType, string $realName, string $content, ?string $comment = null): int
    {
        $this->Entity->canOrExplode('write');

        $allowedFileTypes = array('pdf', 'png', 'mol', 'json', 'zip');
        if (!in_array($fileType, $allowedFileTypes, true)) {
            throw new IllegalActionException('Bad filetype!');
        }

        if ($fileType === 'png') {
            // get the image in binary
            $content = str_replace(array('data:image/png;base64,', ' '), array('', '+'), $content);
            $content = base64_decode($content, true);
            if ($content === false) {
                throw new RuntimeException('Could not decode content!');
            }
        }

        // add file extension if it wasn't provided
        if (Tools::getExt($realName) === 'unknown') {
            $realName .= '.' . $fileType;
        }
        // create a temporary file so we can upload it using create()
        $tmpFilePath = FsTools::getCacheFile();
        $tmpFilePathFs = FsTools::getFs(dirname($tmpFilePath));
        $tmpFilePathFs->write(basename($tmpFilePath), $content);

        $params = new CreateUpload($realName, $tmpFilePath, $comment);
        return $this->create($params);
    }

    /**
     * Read from current id
     */
    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            return $this->readAllNormal();
        }

        $sql = 'SELECT * FROM uploads WHERE id = :id AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':state', self::STATE_NORMAL, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    /**
     * Read all uploads for an item
     */
    public function readAll(): array
    {
        $sql = 'SELECT * FROM uploads WHERE item_id = :id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readAllNormal(): array
    {
        // we read all but only return the ones with normal state
        return array_filter($this->readAll(), function ($u) {
            return ((int) $u['state']) === self::STATE_NORMAL;
        });
    }

    public function update(UploadParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('write');
        if ($params->getTarget() === 'file') {
            return $this->replace($params->getFile());
        }
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
        // set the check here so entityData gets loaded
        $this->Entity->canOrExplode('write');
        $uploadArr = $this->read(new ContentParams());
        // check that the filename is not in the body. see #432
        if (strpos($this->Entity->entityData['body'], $uploadArr['long_name'])) {
            throw new ImproperActionException(_('Please make sure to remove any reference to this file in the body!'));
        }
        return $this->nuke();
    }

    /**
     * Delete all uploaded files for an entity
     */
    public function destroyAll(): void
    {
        // this will include the archived/deleted ones
        $uploadArr = $this->readAll();

        foreach ($uploadArr as $upload) {
            $this->setId((int) $upload['id']);
            $this->nuke();
        }
    }

    /**
     * This function will not remove the files but set them to "deleted" state
     * A manual purge must be made by sysadmin if they wish to really remove them.
     */
    private function nuke(): bool
    {
        return $this->update(new UploadParams((string) self::STATE_DELETED, 'state'));
    }

    /**
     * Attached files are immutable (change history is kept), so the current
     * file gets its state changed to "archived" and a new one is added
     */
    private function replace(UploadedFile $file): bool
    {
        // read the current one to get the comment
        $upload = $this->read(new ContentParams());
        $params = new CreateUpload($file->getClientOriginalName(), $file->getPathname(), $upload['comment']);
        $this->create($params);

        return $this->update(new UploadParams((string) self::STATE_ARCHIVED, 'state'));
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

    /**
     * Make the final SQL request to store the file
     */
    private function dbInsert(string $realName, string $longName, string $hash, int $filesize, int $storage, ?string $comment = null): int
    {
        $comment ??= 'Click to add a comment';

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
            filesize
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
            :filesize
        )';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        // comment can be edited after upload
        // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
        $req->bindValue(':comment', $comment);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);
        $req->bindParam(':storage', $storage, PDO::PARAM_INT);
        $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }
}
