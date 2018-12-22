<?php
/**
 * \Elabftw\Elabftw\Uploads
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use Exception;
use Gmagick;
use PDO;
use Symfony\Component\HttpFoundation\Request;

/**
 * All about the file uploads
 */
class Uploads implements CrudInterface
{
    /** @var AbstractEntity $Entity an entity */
    public $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var string $hashAlgorithm what algo for hashing */
    private $hashAlgorithm = 'sha256';

    /** @var string $uploadsPath absolute path to uploads folder */
    private $uploadsPath;

    /** @var int BIG_FILE_THRESHOLD size of a file in bytes above which we don't process it (5 Mb) */
    private const BIG_FILE_THRESHOLD = 5000000;

    /** @var array MOL_EXTENSIONS list of extensions understood by 3Dmol.js see http://3dmol.csb.pitt.edu/doc/types.html */
    public const MOL_EXTENSIONS = array(
        'cdjson',
        'cif',
        'cube',
        'gro',
        'json',
        'mcif',
        'mmtf',
        'mol2',
        'pdb',
        'pqr',
        'prmtop',
        'sdf',
        'vasp',
        'xyz'
    );

    /**
     * Constructor
     *
     * @param AbstractEntity|null $entity instance of Experiments or Database
     */
    public function __construct(?AbstractEntity $entity = null)
    {
        if ($entity !== null) {
            $this->Entity = $entity;
        }
        $this->Db = Db::getConnection();
        $this->uploadsPath = \dirname(__DIR__, 2) . '/uploads/';
    }

    /**
     * Create a clean filename
     * Remplace all non letters/numbers by '.' (this way we don't lose the file extension)
     *
     * @param string $rawName The name of the file as it was on the user's computer
     * @return string The cleaned filename
     */
    private function getSanitizedName(string $rawName): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '.', $rawName);
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
            if (\copy($orig, $dest) !== true) {
                throw new FilesystemErrorException('Error while moving the file. Check folder permissons!');
            }
            if (\unlink($orig) !== true) {
                throw new FilesystemErrorException('Error deleting file!');
            }
        }

        if (\rename($orig, $dest) !== true) {
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
            return hash_file($this->hashAlgorithm, $file);
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
     * Create a unique long filename with a folder
     *
     * @return string the path for storing the file
     */
    protected function getCleanName(): string
    {
        $hash = \hash("sha512", \bin2hex(\random_bytes(16)));
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $folderPath = $this->uploadsPath . $folder;
        if (!is_dir($folderPath) && !mkdir($folderPath, 0700, true) && !is_dir($folderPath)) {
            throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
        }
        return $folder . '/' . $hash;
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @param string|null $comment The file comment
     * @throws DatabaseErrorException
     * @return void
     */
    private function dbInsert(string $realName, string $longName, string $hash, ?string $comment = null): void
    {
        if ($comment === null) {
            $comment = 'Click to add a comment';
        }

        $sql = "INSERT INTO uploads(
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
        )";

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

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Main method for normal file upload
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    public function create(Request $request): void
    {
        $this->Entity->canOrExplode('write');

        $realName = $this->getSanitizedName($request->files->get('file')->getClientOriginalName());
        $this->checkExtension($realName);

        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = $this->uploadsPath . $longName;

        // Try to move the file to its final place
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);

        // final sql
        $this->dbInsert($realName, $longName, $this->getHash($fullPath));
        $this->makeThumb($fullPath);
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

        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = $this->uploadsPath . $longName;

        $this->moveFile($filePath, $fullPath);

        $this->dbInsert($realName, $longName, $this->getHash($fullPath), $comment);
        $this->makeThumb($fullPath);
    }

    /**
     * Create an upload from a string, from Chemdoodle or Doodle
     *
     * @param string $fileType 'mol' or 'png'
     * @param string $content content of the file
     * @return void
     */
    public function createFromString(string $fileType, string $content): void
    {
        $this->Entity->canOrExplode('write');

        if ($fileType === 'png') {
            $realName = 'Doodle.png';
            // get the image in binary
            $content = str_replace(array('data:image/png;base64,', ' '), array('', '+'), $content);
            $content = base64_decode($content);
        } elseif ($fileType === 'mol') {
            $realName = 'Mol-file.mol';
        } else {
            throw new IllegalActionException('Bad filetype!');
        }

        $longName = $this->getCleanName() . "." . $fileType;
        $fullPath = $this->uploadsPath . $longName;

        if (!empty($content) && !file_put_contents($fullPath, $content)) {
            throw new FilesystemErrorException("Could not write to file!");
        }

        $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Read infos from an upload ID
     *
     * @param int $id id of the uploaded item
     * @throws DatabaseErrorException
     * @return array
     */
    public function readFromId(int $id): array
    {
        $sql = "SELECT * FROM uploads WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return $req->fetch();
    }

    /**
     * Read all uploads for an item
     *
     * @throws DatabaseErrorException
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return $req->fetchAll();
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
        $this->Entity->canOrExplode('write');
        // SQL to update single file comment
        $sql = "UPDATE uploads SET comment = :comment WHERE id = :id AND item_id = :item_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':comment', $comment);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Create a jpg thumbnail from images of type jpeg, png, gif, tiff, eps and pdf.
     *
     * @param string $src Full path to the original file
     * @return bool
     */
    public function makeThumb(string $src): bool
    {
        $dest = $src . '_th.jpg';
        $desiredWidth = 100;

        if (\is_readable($src) === false) {
            throw new FilesystemErrorException("ERROR: file not found! (" . \substr($src, 0, 42) . "…)");
        }

        // we don't want to work on too big images
        // and we don't want to do it again if it exists already
        if (filesize($src) > self::BIG_FILE_THRESHOLD || \file_exists($dest)) {
            return false;
        }

        // get mime type of the file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $src);

        if (\extension_loaded('gmagick')) {
            // try first with gmagick lib

            // do some sane white-listing. In theory, gmagick handles almost all image formats,
            // but the processing of rarely used formats may be less tested/stable or may have security issues
            // when adding new mime types take care of
            // ambiguities: e.g. image/eps may be a valid application/postscript; image/bmp may also be image/x-bmp or
            // image/x-ms-bmp
            $allowed_mime = array('image/png',
                                'image/jpeg',
                                'image/gif',
                                'image/tiff',
                                'image/x-eps',
                                'image/svg+xml',
                                'application/pdf',
                                'application/postscript');

            if (!\in_array($mime, $allowed_mime, true)) {
                return false;
            }

            // if pdf or postscript, generate thumbnail using the first page (index 0) do the same for postscript files
            // sometimes eps images will be identified as application/postscript as well, but thumbnail generation still
            // works in those cases
            if ($mime === 'application/pdf' || $mime === 'application/postscript') {
                $src .= '[0]';
            }
            // fail silently if thumbnail generation does not work to keep file upload field functional
            // originally introduced due to issue #415.
            try {
                $image = new Gmagick($src);
            } catch (Exception $e) {
                return false;
            }
            // create thumbnail of width 100px; height is calculated automatically to keep the aspect ratio
            $image->thumbnailimage($desiredWidth, 0);
            // create the physical thumbnail image to its destination (85% quality)
            $image->setCompressionQuality(85);
            $image->write($dest);
            $image->clear();

        // if we don't have gmagick, try with gd
        } elseif (extension_loaded('gd')) {
            // the used fonction is different depending on extension
            if ($mime === 'image/jpeg') {
                $sourceImage = imagecreatefromjpeg($src);
            } elseif ($mime === 'image/png') {
                $sourceImage = imagecreatefrompng($src);
            } elseif ($mime === 'image/gif') {
                $sourceImage = imagecreatefromgif($src);
            } else {
                return false;
            }

            if ($sourceImage === false) {
                return false;
            }

            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            // find the "desired height" of this thumbnail, relative to the desired width
            $desiredHeight = (int) floor($height * ($desiredWidth / $width));

            // create a new, "virtual" image
            $virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);
            if ($virtualImage === false) {
                return false;
            }

            // copy source image at a resized size
            imagecopyresized($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);

            // create the physical thumbnail image to its destination (85% quality)
            imagejpeg($virtualImage, $dest, 85);

            return true;
        }
        // and if we have no gmagick and no gd, well there's nothing I can do for you boy!
        return false;
    }

    /**
     * Replace an uploaded file by another
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    public function replace(Request $request): void
    {
        $this->Entity->canOrExplode('write');
        $id = filter_var($request->request->get('upload_id'), FILTER_VALIDATE_INT);
        $upload = $this->readFromId($id);
        if (empty($upload)) {
            throw new IllegalActionException('Bad id in upload replace');
        }
        $fullPath = $this->uploadsPath . $upload['long_name'];
        // check user is same as the previously uploaded file
        if ((int) $upload['userid'] !== (int) $this->Entity->Users->userData['userid']) {
            throw new IllegalActionException('User tried to replace an upload of another user.');
        }
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);
        $this->makeThumb($fullPath);
    }

    /**
     * Get the correct class for icon from the extension
     *
     * @param string $ext Extension of the file
     * @return string Class of the fa icon
     */
    public function getIconFromExtension(string $ext): string
    {
        switch ($ext) {
            // ARCHIVE
            case 'zip':
            case 'rar':
            case 'xz':
            case 'gz':
            case 'tgz':
            case '7z':
            case 'bz2':
            case 'tar':
                return 'fa-file-archive';

            // CODE
            case 'py':
            case 'jupyter':
            case 'js':
            case 'm':
            case 'r':
            case 'R':
                return 'fa-file-code';

            // EXCEL
            case 'xls':
            case 'xlsx':
            case 'ods':
            case 'csv':
                return 'fa-file-excel';

            // POWERPOINT
            case 'ppt':
            case 'pptx':
            case 'pps':
            case 'ppsx':
            case 'odp':
                return 'fa-file-powerpoint';

            // VIDEO
            case 'mov':
            case 'avi':
            case 'mp4':
            case 'wmv':
            case 'mpeg':
            case 'flv':
                return 'fa-file-video';

            // WORD
            case 'doc':
            case 'docx':
            case 'odt':
                return 'fa-file-word';

            default:
                return 'fa-file';
        }
    }

    public function getFileSize(string $filePath): int
    {
        return (int) \filesize($this->uploadsPath . $filePath);
    }

    /**
     * Destroy an upload
     *
     * @param int $id id of the upload
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->Entity->canOrExplode('write');

        $uploadArr = $this->readFromId($id);

        // remove thumbnail
        $thumbPath = $this->uploadsPath . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath)) {
            if (unlink($thumbPath) !== true) {
                throw new FilesystemErrorException('Could not delete file!');
            }
        }
        // now delete file from filesystem
        $filePath = $this->uploadsPath . $uploadArr['long_name'];
        if (file_exists($filePath)) {
            if (unlink($filePath) !== true) {
                throw new FilesystemErrorException('Could not delete file!');
            }
        }

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = "DELETE FROM uploads WHERE id = :id AND type = :type";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
}
