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
     * Main method for normal file upload
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     */
    public function create(Request $request): bool
    {
        $realName = $this->getSanitizedName($request->files->get('file')->getClientOriginalName());
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = $this->uploadsPath . $longName;

        // disallow upload of php files
        if (Tools::getExt($realName) === 'php') {
            throw new Exception('PHP files are forbidden!');
        }

        // Try to move the file to its final place
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);

        // final sql
        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Called from ImportZip class
     *
     * @param string $filePath absolute path to the file
     * @param string $comment
     * @return bool
     */
    public function createFromLocalFile(string $filePath, string $comment): bool
    {
        $realName = basename($filePath);
        $ext = Tools::getExt($realName);

        // disallow upload of php files
        if ($ext === 'php') {
            throw new Exception('PHP files are forbidden!');
        }

        $longName = $this->getCleanName() . "." . $ext;
        $finalPath = $this->uploadsPath . $longName;

        $this->moveFile($filePath, $finalPath);

        return $this->dbInsert($realName, $longName, $this->getHash($finalPath), $comment);
    }

    /**
     * Create an upload from a string, from Chemdoodle or Doodle
     *
     * @param string $fileType 'mol' or 'png'
     * @param string $content content of the file
     * @return bool
     */
    public function createFromString(string $fileType, string $content): bool
    {
        if ($fileType === 'png') {
            $realName = 'Doodle.png';
            // get the image in binary
            $content = str_replace(array('data:image/png;base64,', ' '), array('', '+'), $content);
            $content = base64_decode($content);
        } elseif ($fileType === 'mol') {
            $realName = 'Mol-file.mol';
        } else {
            throw new Exception('Bad type!');
        }

        $longName = $this->getCleanName() . "." . $fileType;
        $fullPath = $this->uploadsPath . $longName;

        if (!empty($content) && !file_put_contents($fullPath, $content)) {
            throw new Exception("Could not write to file");
        }

        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
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
     * @throws Exception if cannot move the file
     * @return bool
     */
    private function moveFile(string $orig, string $dest): bool
    {
        // fix for FreeBSD and rename across different filesystems
        // see http://php.net/manual/en/function.rename.php#117590
        if (PHP_OS === 'FreeBSD') {
            return copy($orig, $dest) && unlink($orig);
        }

        if (!rename($orig, $dest)) {
            throw new Exception('Error while moving the file. Check folder permissons!');
        }
        return true;
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
     * Create a unique long filename with a folder
     *
     * @throws Exception
     * @return string the path for storing the file
     */
    protected function getCleanName(): string
    {
        $hash = \hash("sha512", \bin2hex(\random_bytes(16)));
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $folderPath = $this->uploadsPath . $folder;
        if (!is_dir($folderPath) && !mkdir($folderPath, 0700, true) && !is_dir($folderPath)) {
            throw new Exception('Cannot create folder! Check permissions of uploads folder.');
        }
        return $folder . '/' . $hash;
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @param string|null $comment
     * @throws Exception if request fail
     * @return bool
     */
    private function dbInsert(string $realName, string $longName, string $hash, ?string $comment = null): bool
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
        $req->bindParam(':userid', $this->Entity->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);

        return $req->execute();
    }

    /**
     * Read infos from an upload ID
     *
     * @param int $id id of the uploaded item
     * @return array
     */
    public function readFromId(int $id): array
    {
        $sql = "SELECT * FROM uploads WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read all uploads for an item
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update the comment of a file. We also pass the itemid to make sure we update
     * the comment associated with the item sent to the controller. Because write access
     * is checked on this value.
     *
     * @param int $id id of the file
     * @param string $comment
     * @return bool
     */
    public function updateComment(int $id, string $comment): bool
    {
        // SQL to update single file comment
        $sql = "UPDATE uploads SET comment = :comment WHERE id = :id AND item_id = :item_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':comment', $comment);

        return $req->execute();
    }

    /**
     * Create a jpg thumbnail from images of type jpeg, png, gif, tiff, eps and pdf.
     *
     * @param string $src Path to the original file
     * @param string $dest Path to the place to save the thumbnail
     * @param int $desiredWidth Width of the thumbnail (height is automatic depending on width)
     * @return bool
     */
    public function makeThumb(string $src, string $dest, int $desiredWidth): bool
    {
        // we don't want to work on too big images
        if (filesize($src) > self::BIG_FILE_THRESHOLD) {
            return false;
        }

        // get mime type of the file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $src);

        if (extension_loaded('gmagick')) {
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

            if (\in_array($mime, $allowed_mime, true)) {
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
            } else {
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
     * @return bool
     */
    public function replace(Request $request): bool
    {
        $id = filter_var($request->request->get('upload_id'), FILTER_VALIDATE_INT);
        $upload = $this->readFromId($id);
        if (empty($upload)) {
            throw new Exception('Bad id in upload replace');
        }
        $fullPath = $this->uploadsPath . $upload['long_name'];
        // check user is same as the previously uploaded file
        if ((int) $upload['userid'] !== $this->Entity->Users->userid) {
            return false;
        }
        $this->moveFile($request->files->get('file')->getPathname(), $fullPath);
        $thumbPath = $fullPath . '_th.jpg';
        $this->makeThumb($fullPath, $thumbPath, 100);
        return true;
    }
    /**
     * Destroy an upload
     *
     * @param int $id id of the upload
     * @return bool
     */
    public function destroy(int $id): bool
    {
        $uploadArr = $this->readFromId($id);

        // remove thumbnail
        $thumbPath = $this->uploadsPath . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        // now delete file from filesystem
        $filePath = $this->uploadsPath . $uploadArr['long_name'];
        if (file_exists($thumbPath)) {
            unlink($filePath);
        }

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = "DELETE FROM uploads WHERE id = :id AND type = :type";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        return $req->execute();
    }

    /**
     * Delete all uploaded files for an entity
     *
     * @return bool
     */
    public function destroyAll(): bool
    {
        $uploadArr = $this->readAll();
        $resultsArr = array();

        foreach ($uploadArr as $upload) {
            $resultsArr[] = $this->destroy((int) $upload['id']);
        }

        if (\in_array(false, $resultsArr, true)) {
            throw new Exception('Error deleting uploads.');
        }

        return true;
    }
}
