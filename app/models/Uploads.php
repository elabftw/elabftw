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
namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the file uploads
 */
class Uploads extends Entity
{
    /** pdo object */
    protected $pdo;

    /** what algo for hashing */
    private $hashAlgorithm = 'sha256';

    /** instance of Experiments or Database */
    public $Entity;

    /**
     * Constructor
     *
     * @param Entity $entity instance of Experiments or Database
     */
    public function __construct(Entity $entity)
    {
        $this->pdo = Db::getConnection();

        $this->Entity = $entity;
    }

    /**
     * Main method for normal file upload
     *
     * @param array $file $_FILES
     * @return bool
     */
    public function create($file)
    {
        if (!is_array($file) || count($file) === 0) {
            throw new Exception('No files received');
        }

        $realName = $this->getSanitizedName($file['file']['name']);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        // disallow upload of php files
        if (Tools::getExt($realName) === 'php') {
            throw new Exception('PHP files are forbidden!');
        }

        // Try to move the file to its final place
        $this->moveFile($file['file']['tmp_name'], $fullPath);

        // final sql
        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Called from ImportZip class
     *
     * @param string $file The string of the local file path stored in .elabftw.json of the zip archive
     * @return bool
     */
    public function createFromLocalFile($file)
    {
        if (!is_readable($file)) {
            throw new Exception('No file here!');
        }

        $realName = basename($file);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        $this->moveFile($file, $fullPath);

        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Create an upload from a string, from Chemdoodle or Doodle
     *
     * @param string $fileType 'mol' or 'png'
     * @param string $string
     * @return bool
     */
    public function createFromString($fileType, $string)
    {
        if ($fileType === 'png') {
            $realName = 'Doodle.png';
            // get the image in binary
            $string = str_replace('data:image/png;base64,', '', $string);
            $string = str_replace(' ', '+', $string);
            $string = base64_decode($string);
        } elseif ($fileType === 'mol') {
            $realName = 'Mol-file.mol';
        } else {
            throw new Exception('Bad type!');
        }

        $longName = $this->getCleanName() . "." . $fileType;
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        if (!empty($string) && !file_put_contents($fullPath, $string)) {
            throw new Exception("Could not write to file");
        }

        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Upload a png image from Doodle canvas
     *
     * @param string $png
     * @return bool
     */

    /**
     * Create a clean filename
     * Remplace all non letters/numbers by '.' (this way we don't lose the file extension)
     *
     * @param string $rawName The name of the file as it was on the user's computer
     * @return string The cleaned filename
     */
    private function getSanitizedName($rawName)
    {
        return preg_replace('/[^A-Za-z0-9]/', '.', $rawName);
    }

    /**
     * Place a file somewhere
     *
     * @param string $orig from
     * @param string $dest to
     * @throws Exception if cannot move the file
     */
    private function moveFile($orig, $dest)
    {
        if (!rename($orig, $dest)) {
            throw new Exception('Error while moving the file. Check folder permissons!');
        }
    }

    /**
     * Generate the hash based on selected algorithm
     *
     * @param string $file The full path to the file
     * @return string|null the hash or null if file is too big
     */
    private function getHash($file)
    {
        if (filesize($file) < 5000000) {
            return hash_file($this->hashAlgorithm, $file);
        }

        return null;
    }

    /**
     * Create a unique long filename with a folder
     *
     * @return string the path for storing the file
     */
    protected function getCleanName()
    {
        $hash = hash("sha512", uniqid(rand(), true));
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $folderPath = ELAB_ROOT . 'uploads/' . $folder;
        if (!is_writable($folderPath)) {
            mkdir($folderPath);
        }
        return $folder . '/' . $hash;
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @throws Exception if request fail
     * @return bool
     */
    private function dbInsert($realName, $longName, $hash)
    {
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

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        // comment can be edited after upload
        // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
        $req->bindValue(':comment', 'Click to add a comment');
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindParam(':type', $this->Entity->type);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);

        return $req->execute();
    }

    /**
     * Read infos from an upload ID and type
     * Type can be experiments, timestamp-pdf, items, timestamp-token
     *
     * @param int $id id of the uploaded item
     * @return array
     */
    public function readFromId($id)
    {
        $sql = "SELECT * FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $this->Entity->type);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read all uploads for an item
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        $req->bindParam(':type', $this->Entity->type);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update the comment of a file
     *
     * @param int $id id of the file
     * @param string $comment
     * @return bool
     */
    public function updateComment($id, $comment)
    {
        // SQL to update single file comment
        $sql = "UPDATE uploads SET comment = :comment WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':comment', $comment);

        return $req->execute();
    }

    /**
     * Create a jpg thumbnail from images of type jpg, png or gif.
     *
     * @param string $src Path to the original file
     * @param string $dest Path to the place to save the thumbnail
     * @param int $desiredWidth Width of the thumbnail (height is automatic depending on width)
     * @return null|false
     */
    public function makeThumb($src, $dest, $desiredWidth)
    {
        // we don't want to work on too big images
        // put the limit to 5 Mbytes
        if (filesize($src) > 5000000) {
            return false;
        }

        // get mime type of the file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $src);
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
        $desiredHeight = floor($height * ($desiredWidth / $width));

        // create a new, "virtual" image
        $virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);

        // copy source image at a resized size
        imagecopyresized($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);

        // create the physical thumbnail image to its destination (85% quality)
        imagejpeg($virtualImage, $dest, 85);
    }

    /**
     * Destroy an upload
     *
     * @param int $id id of the upload
     * @return bool
     */
    public function destroy($id)
    {
        $uploadArr = $this->readFromId($id);

        // remove thumbnail
        $thumbPath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        // now delete file from filesystem
        $filePath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'];
        unlink($filePath);

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = "DELETE FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $this->Entity->type);

        return $req->execute();
    }

    /**
     * Delete all uploaded files for an entity
     *
     * @return bool
     */
    public function destroyAll()
    {
        $uploadArr = $this->readAll();
        $resultsArr = array();

        foreach ($uploadArr as $upload) {
            $resultsArr[] = $this->destroy($upload['id']);
        }

        if (in_array(false, $resultsArr)) {
            throw new Exception('Error deleting uploads.');
        }

        return true;
    }
}
