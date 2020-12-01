<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use function copy;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Extensions;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DestroyableInterface;
use Elabftw\Services\Filter;
use Elabftw\Services\MakeThumbnail;
use Elabftw\Traits\UploadTrait;
use function exif_read_data;
use function file_exists;
use function function_exists;
use Gmagick;
use function in_array;
use function mb_strlen;
use PDO;
use function rename;
use Symfony\Component\HttpFoundation\Request;
use function unlink;

/**
 * All about the file uploads
 */
class Uploads implements DestroyableInterface
{
    use UploadTrait;

    /** @var int BIG_FILE_THRESHOLD size of a file in bytes above which we don't process it (50 Mb) */
    private const BIG_FILE_THRESHOLD = 50000000;

    /** @var AbstractEntity $Entity an entity */
    public $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var string $hashAlgorithm what algo for hashing */
    private $hashAlgorithm = 'sha256';

    /**
     * Constructor
     *
     * @param AbstractEntity $entity instance of Experiments or Database
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }

    /**
     * Main method for normal file upload
     *
     * @param Request $request
     * @return void
     */
    public function create(Request $request): void
    {
        $this->Entity->canOrExplode('write');

        $realName = Filter::forFilesystem($request->files->get('file')->getClientOriginalName());
        $this->checkExtension($realName);
        $ext = Tools::getExt($realName);

        $longName = $this->getLongName() . '.' . $ext;
        $fullPath = $this->getUploadsPath() . $longName;

        // Try to move the file to its final place
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);

        // rotate the image if we can find the orientation in the exif data
        // maybe php-exif extension isn't loaded
        if (function_exists('exif_read_data') && in_array($ext, Extensions::HAS_EXIF, true)) {
            $exifData = exif_read_data($fullPath);
            if ($exifData !== false) {
                $image = new Gmagick($fullPath);
                // default is 75
                $image->setCompressionQuality(100);
                $rotationAngle = $this->getRotationAngle($exifData);
                // only do it if needed
                if ($rotationAngle !== 0) {
                    $image->rotateimage('#000', $rotationAngle);
                    $image->write($fullPath);
                }
            }
        }
        // final sql
        $this->dbInsert($realName, $longName, $this->getHash($fullPath));
        $MakeThumbnail = new MakeThumbnail($fullPath);
        $MakeThumbnail->makeThumb();
    }

    /**
     * Called from ImportZip class
     *
     * @param string $filePath absolute path to the file
     * @param string $comment
     * @return void
     */
    public function createFromLocalFile(string $filePath, string $comment): void
    {
        $realName = basename($filePath);
        $this->checkExtension($realName);

        $longName = $this->getLongName() . '.' . Tools::getExt($realName);
        $fullPath = $this->getUploadsPath() . $longName;

        $this->moveFile($filePath, $fullPath);

        $this->dbInsert($realName, $longName, $this->getHash($fullPath), $comment);
        $MakeThumbnail = new MakeThumbnail($fullPath);
        $MakeThumbnail->makeThumb();
    }

    /**
     * Create an upload from a string, from Chemdoodle or Doodle
     *
     * @param string $fileType 'mol' or 'png'
     * @param string $realName name of the file
     * @param string $content content of the file
     * @return int
     */
    public function createFromString(string $fileType, string $realName, string $content): int
    {
        $this->Entity->canOrExplode('write');

        $allowedFileTypes = array('png', 'mol', 'json');
        if (!in_array($fileType, $allowedFileTypes, true)) {
            throw new IllegalActionException('Bad filetype!');
        }

        if ($fileType === 'png') {
            // get the image in binary
            $content = str_replace(array('data:image/png;base64,', ' '), array('', '+'), $content);
            $content = base64_decode($content, true);
        }

        // make sure the file has a name
        if (empty($realName)) {
            $realName = 'untitled';
        }

        $realName = filter_var($realName, FILTER_SANITIZE_STRING) . '.' . $fileType;
        $longName = $this->getLongName() . '.' . $fileType;
        $fullPath = $this->getUploadsPath() . $longName;

        if (!empty($content) && !file_put_contents($fullPath, $content)) {
            throw new FilesystemErrorException('Could not write to file!');
        }

        $uploadId = $this->dbInsert($realName, $longName, $this->getHash($fullPath));
        $MakeThumbnail = new MakeThumbnail($fullPath);
        $MakeThumbnail->makeThumb();

        return $uploadId;
    }

    /**
     * Read info from an upload ID
     *
     * @param int $id id of the uploaded item
     * @throws DatabaseErrorException
     * @return array
     */
    public function readFromId(int $id): array
    {
        $sql = 'SELECT * FROM uploads WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('Nothing to show with this id');
        }
        return $res;
    }

    /**
     * Read all uploads for an item
     *
     * @throws DatabaseErrorException
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT * FROM uploads WHERE item_id = :id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Update the comment of a file. We also pass the itemid to make sure we update
     * the comment associated with the item sent to the controller. Because write access
     * is checked on this value.
     *
     * @param int $id id of the file
     * @param string $comment
     * @throws DatabaseErrorException
     * @return void
     */
    public function updateComment(int $id, string $comment): void
    {
        // check length
        if (mb_strlen($comment) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        $this->Entity->canOrExplode('write');
        // SQL to update single file comment
        $sql = 'UPDATE uploads SET comment = :comment WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':comment', $comment);
        $this->Db->execute($req);
    }

    /**
     * Replace an uploaded file by another
     *
     * @param Request $request
     * @return void
     */
    public function replace(Request $request): void
    {
        $this->Entity->canOrExplode('write');
        $upload = $this->readFromId((int) $request->request->get('upload_id'));
        $fullPath = $this->getUploadsPath() . $upload['long_name'];
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);
        $MakeThumbnail = new MakeThumbnail($fullPath);
        $MakeThumbnail->makeThumb(true);

        $sql = 'UPDATE uploads SET datetime = CURRENT_TIMESTAMP WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $request->request->get('upload_id'), PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Get the correct class for icon from the extension
     *
     * @param string $ext Extension of the file
     * @return string Class of the fa icon
     */
    public function getIconFromExtension(string $ext): string
    {
        if (in_array($ext, Extensions::ARCHIVE, true)) {
            return 'fa-file-archive';
        }
        if (in_array($ext, Extensions::CODE, true)) {
            return 'fa-file-code';
        }
        if (in_array($ext, Extensions::SPREADSHEET, true)) {
            return 'fa-file-excel';
        }
        if (in_array($ext, Extensions::IMAGE, true)) {
            return 'fa-file-image';
        }
        if ($ext === 'pdf') {
            return 'fa-file-pdf';
        }
        if (in_array($ext, Extensions::PRESENTATION, true)) {
            return 'fa-file-powerpoint';
        }
        if (in_array($ext, Extensions::VIDEO, true)) {
            return 'fa-file-video';
        }
        if (in_array($ext, Extensions::DOCUMENT, true)) {
            return 'fa-file-word';
        }

        return 'fa-file';
    }

    /**
     * Destroy an upload
     */
    public function destroy(int $id): bool
    {
        $this->Entity->canOrExplode('write');

        $uploadArr = $this->readFromId($id);

        // remove thumbnail
        $thumbPath = $this->getUploadsPath() . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath) && unlink($thumbPath) !== true) {
            throw new FilesystemErrorException('Could not delete file!');
        }
        // now delete file from filesystem
        $filePath = $this->getUploadsPath() . $uploadArr['long_name'];
        if (file_exists($filePath) && unlink($filePath) !== true) {
            throw new FilesystemErrorException('Could not delete file!');
        }

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = 'DELETE FROM uploads WHERE id = :id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        return $this->Db->execute($req);
    }

    /**
     * Delete all uploaded files for an entity
     *
     * @return void
     */
    public function destroyAll(): void
    {
        $uploadArr = $this->readAll();

        foreach ($uploadArr as $upload) {
            $this->destroy((int) $upload['id']);
        }
    }

    /**
     * Get the rotation angle from exif data
     *
     * @param array<string, mixed> $exifData
     * @return int
     */
    private function getRotationAngle(array $exifData): int
    {
        if (empty($exifData['Orientation'])) {
            return 0;
        }
        switch ($exifData['Orientation']) {
            case 1:
                return 0;
            case 3:
                return 180;
            case 6:
                return 90;
            case 8:
                return -90;
            default:
                return 0;
        }
    }

    /**
     * Place a file somewhere
     *
     * @param string $orig from
     * @param string $dest to
     * @throws FilesystemErrorException
     * @return void
     */
    private function moveFile(string $orig, string $dest): void
    {
        // fix for FreeBSD and rename across different filesystems
        // see http://php.net/manual/en/function.rename.php#117590
        if (PHP_OS === 'FreeBSD') {
            if (copy($orig, $dest) !== true) {
                throw new FilesystemErrorException('Error while moving the file. Check folder permissons!');
            }
            if (unlink($orig) !== true) {
                throw new FilesystemErrorException('Error deleting file!');
            }
        }

        if (rename($orig, $dest) !== true) {
            throw new FilesystemErrorException('Error while moving the file. Check folder permissons!');
        }
    }

    /**
     * Generate the hash based on selected algorithm
     *
     * @param string $file The full path to the file
     * @return string the hash or an empty string if file is too big
     */
    private function getHash(string $file): string
    {
        if (filesize($file) < self::BIG_FILE_THRESHOLD) {
            $hash = hash_file($this->hashAlgorithm, $file);
            if ($hash === false) {
                throw new ImproperActionException('Error creating hash from file!');
            }
            return $hash;
        }

        return '';
    }

    /**
     * Check if extension is allowed for upload
     *
     * @param string $realName The name of the file
     * @return void
     */
    private function checkExtension(string $realName): void
    {
        if (Tools::getExt($realName) === 'php') {
            throw new ImproperActionException('PHP files are forbidden!');
        }
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @param string|null $comment The file comment
     * @throws DatabaseErrorException
     * @return int
     */
    private function dbInsert(string $realName, string $longName, string $hash, ?string $comment = null): int
    {
        if ($comment === null) {
            $comment = 'Click to add a comment';
        }

        $sql = 'INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            userid,
            type,
            hash,
            hash_algorithm
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :userid,
            :type,
            :hash,
            :hash_algorithm
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
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }
}
