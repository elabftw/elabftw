<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
namespace Elabftw\Elabftw;

use \Exception;
use \ZipArchive;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \FileSystemIterator;

class ImportZip
{
    // number of item we have inserted
    public $inserted = 0;
    private $tmpPath;
    private $fileTmpName;
    private $json;

    private $itemType;
    private $title;
    private $body;
    // the newly created id of the imported item
    // we need it for linking attached file(s) to the the new item
    private $newItemId;

    public function __construct($zipfile, $itemType)
    {
        $this->fileTmpName = $zipfile;
        $this->itemType = $itemType;
        // this is where we will extract the zip
        $this->tmpPath = ELAB_ROOT . 'uploads/tmp/' . uniqid();
        if (!mkdir($this->tmpPath)) {
            throw new Exception('Cannot create temporary folder');
        }

        $this->extractZip();
        $this->readJson();
        $this->checkItemType();
        $this->importAll();
    }

    /*
     * Extract the zip to the temporary folder
     *
     * @return bool
     */
    private function extractZip()
    {
        $zip = new \ZipArchive;
        if ($zip->open($this->fileTmpName) && $zip->extractTo($this->tmpPath)) {
            return true;
        } else {
            throw new Exception('Cannot open zip file!');
        }
    }

    /*
     * We get all the info we need from the embedded .json file
     *
     */
    private function readJson()
    {
        $file = $this->tmpPath . "/.elabftw.json";
        $content = file_get_contents($file);
        $this->json = json_decode($content, true);
        // we can only import database items, not experiments
        if ($this->json[0]['type'] === 'experiments') {
            throw new Exception('Cannot import an experiment!');
        }
    }

    /*
     * Item type will be a number
     *
     */
    private function checkItemType()
    {
        if (!is_pos_int($this->itemType)) {
            throw new Exception('No cookie found!');
        }
    }

    /*
     * The main SQL to create a new item with the title and body we have
     *
     */
    private function importData()
    {
        global $pdo;

        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id'], \PDO::PARAM_INT);
        $req->bindParam(':title', $this->title);
        $req->bindParam(':date', kdate());
        $req->bindParam(':body', $this->body);
        $req->bindParam(':userid', $_SESSION['userid'], \PDO::PARAM_INT);
        $req->bindParam(':type', $this->itemType);

        if (!$req->execute()) {
            throw new Exception('Cannot import in database!');
        }
        // needed in importFile()
        $this->newItemId = $pdo->lastInsertId();
    }

    /*
     * If files are attached we want them!
     *
     * @param string $file The path of the file in the archive
     */
    private function importFile($file)
    {
        global $pdo;

        // first move the file to the uploads folder
        $longName = hash("sha512", uniqid(rand(), true)) . '.' . get_ext($file);
        $newPath = ELAB_ROOT . 'uploads/' . $longName;
        if (!rename($this->tmpPath . '/' . $file, $newPath)) {
            throw new Exception('Cannot rename file!');
        }

        // make md5sum
        $md5 = hash_file('md5', $newPath);

        // now insert it in sql
        $sql = "INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            userid,
            type,
            md5
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :userid,
            :type,
            :md5
        )";

        $req = $pdo->prepare($sql);
        $req->bindParam(':real_name', basename($file));
        $req->bindParam(':long_name', $longName);
        $req->bindValue(':comment', 'Click to add a comment');
        $req->bindParam(':item_id', $this->newItemId);
        $req->bindParam(':userid', $_SESSION['userid']);
        $req->bindValue(':type', 'items');
        $req->bindParam(':md5', $md5);

        if (!$req->execute()) {
            throw new Exception('Cannot import in database!');
        }
    }

    /*
     * Loop the json and import the items.
     *
     */
    private function importAll()
    {
        foreach ($this->json as $item) {
            $this->title = $item['title'];
            $this->body = $item['body'];
            $this->importData();
            if (is_array($item['files'])) {
                foreach ($item['files'] as $file) {
                    $this->importFile($file);
                }
            }
            $this->inserted += 1;
        }
    }

    /*
     * Cleanup : remove the temporary folder created
     *
     */
    public function __destruct()
    {
        // first remove content
        $di = new \RecursiveDirectoryIterator($this->tmpPath, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
        // and remove folder itself
        rmdir($this->tmpPath);
    }
}
