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

use \Elabftw\Elabftw\MakePdf;
use \ZipArchive;
use \Exception;

class MakeZip
{
    private $idList;
    private $idArr = array();

    private $zipRealName;
    private $zipRelativePath;
    private $zipAbsolutePath;

    private $zip;
    private $table;
    private $filesToDelete = array();
    private $cleanTitle;

    private $zipped;
    // the path to attached files in the zip
    private $fileArr = array();
    private $jsonArr = array();

    private $folder;


    /*
     * Provide it with the $_GET['id'] and $_GET['type']
     *
     */
    public function __construct($idList, $type)
    {
        // we check first if the zip extension is here
        if (!class_exists('ZipArchive')) {
            throw new Exception("You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in /etc/php/php.ini.");
        }

        $this->idList = $idList;
        $this->table = $type;
        $this->checkTable();

        $this->zipRealName = hash("sha512", uniqid(rand(), true)) . ".zip";
        $this->zipRelativePath = 'uploads/tmp/' . $this->zipRealName;
        $this->zipAbsolutePath = ELAB_ROOT . $this->zipRelativePath;
        $this->createZipArchive();
        $this->loopIdArr();

    }
    /*
     * Return the relative path of the zip (uploads/tmp/<hash>.zip)
     *
     * @return string
     */
    public function getZipRelativePath()
    {
        return $this->zipRelativePath;
    }

    /*
     * This is the name of the file that will get downloaded
     *
     * @return string
     */
    public function getZipName()
    {
        $ext = '.elabftw.zip';

        if (count($this->idArr) === 1) {
            return $this->zipped['date'] . "-" . $this->cleanTitle . $ext;
        } else {
            return kdate() . $ext;
        }
    }

    /*
     * Initiate the zip object and the archive
     *
     */
    private function createZipArchive()
    {
        $this->zip = new \ZipArchive;

        if (!$this->zip->open($this->zipAbsolutePath, ZipArchive::CREATE)) {
            throw new Exception('Could not open zip file!');
        }
    }

    /*
     * Validate the $_GET['type'] we have
     *
     */
    private function checkTable()
    {
        $correctValuesArr = array('experiments', 'items');
        if (!in_array($this->table, $correctValuesArr)) {
            throw new Exception('Bad type!');
        }
    }

    /*
     * Populate $this->zipped
     *
     */
    private function getInfoFromId($id)
    {
        global $pdo;

        // SQL to get info on the item we are zipping
        if ($this->table === 'experiments') {
            $sql = "SELECT * FROM experiments WHERE id = :id LIMIT 1";
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id, \PDO::PARAM_INT);
            $req->execute();
            $this->zipped = $req->fetch();
            if ($this->zipped['userid'] != $_SESSION['userid']) {
                throw new Exception(_("You are trying to download an experiment you don't own!"));
            }

        } else {
            $sql = "SELECT items.*,
                items_types.name AS items_typesname
                FROM items
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE items.id = :id LIMIT 1";
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id, \PDO::PARAM_INT);
            $req->execute();
            $this->zipped = $req->fetch();
            if ($this->zipped['team'] != $_SESSION['team_id']) {
                throw new Exception(_("You are trying to download an item you don't own!"));
            }
        }

        // make a title without special char for folder inside .zip
        $this->cleanTitle = preg_replace('/[^A-Za-z0-9]/', '_', stripslashes($this->zipped['title']));
    }

    // add the .asn1 token to the zip archive if the experiment is timestamped
    private function addAsn1Token($id)
    {
        global $pdo;

        if ($this->table === 'experiments' && $this->zipped['timestamped'] == 1) {
            // SQL to get the path of the token
            $sql = "SELECT real_name, long_name FROM uploads WHERE item_id = :id AND type = 'timestamp-token' LIMIT 1";
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id);
            $req->execute();
            $token = $req->fetch();
            // add it to the .zip
            $this->zip->addFile(ELAB_ROOT . 'uploads/' . $token['long_name'], $this->folder . "/" . $token['real_name']);
        }
    }

    // folder begin with date for experiments
    private function nameFolder()
    {
        if ($this->table === 'experiments') {
            $this->folder = $this->zipped['date'] . "-" . $this->cleanTitle;
        } else { // items
            $this->folder = $this->zipped['items_typesname'] . " - " . $this->cleanTitle;
        }
    }

    private function addAttachedFiles($id)
    {
        global $pdo;
        $real_name = array();
        $long_name = array();
        $comment = array();

        // SQL to get filesattached (of the right type)
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND (type = :type OR type = 'exp-pdf-timestamp')";
        $req = $pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $this->table);
        $req->execute();
        while ($uploads = $req->fetch()) {
            $real_name[] = $uploads['real_name'];
            $long_name[] = $uploads['long_name'];
            $comment[] = $uploads['comment'];
        }

        // files attached ?
        $fileNb = count($real_name);
        if ($fileNb > 0) {
            for ($i = 0; $i < $fileNb; $i++) {
                // add files to archive
                $this->zip->addFile(ELAB_ROOT . 'uploads/' . $long_name[$i], $this->folder . "/" . $real_name[$i]);
                // reference them in the json file
                $this->fileArr[] = $this->folder . "/" . $real_name[$i];
            }
        }
    }

    // add PDF to archive
    private function addPdf($id)
    {
        global $pdo;

        $pdfPath = ELAB_ROOT . 'uploads/tmp/' . hash("sha512", uniqid(rand(), true)) . '.pdf';
        $pdf = new \Elabftw\Elabftw\MakePdf($id, $this->table, $pdfPath);
        $this->zip->addFile($pdfPath, $this->folder . '/' . $pdf->getFileName());
        $this->filesToDelete[] = $pdfPath;
    }

    private function addCsv($id)
    {
        // add CSV file to archive
        $csv = new \Elabftw\Elabftw\MakeCsv($id, $this->table);
        $this->zip->addFile($csv->getFilePath(), $this->folder . "/" . $this->cleanTitle . ".csv");
        $this->filesToDelete[] = $csv->getFilePath();
    }

    private function addJson()
    {
        // add a json file that is helpful for importing back the data
        $json = json_encode($this->jsonArr);
        $jsonPath = ELAB_ROOT . 'uploads/tmp/' . hash("sha512", uniqid(rand(), true)) . '.json';
        $tf = fopen($jsonPath, 'w+');
        fwrite($tf, $json);
        fclose($tf);
        // add the json file as hidden file, users don't need to see it
        $this->zip->addFile($jsonPath, ".elabftw.json");
        $this->filesToDelete[] = $jsonPath;
    }

    /*
     * This is where the magic happens
     *
     */
    private function addToZip($id)
    {
        // populate $this->zipped
        $this->getInfoFromId($id);
        $this->nameFolder();
        $this->addAsn1Token($id);
        $this->addAttachedFiles($id);
        $this->addCsv($id);
        $this->addPdf($id);
        // add an entry to the json file
        $this->jsonArr[] = array(
            'type' => $this->table,
            'title' => stripslashes($this->zipped['title']),
            'body' => stripslashes($this->zipped['body']),
            'files' => $this->fileArr
        );
        unset($this->fileArr);
    }

    /*
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     *
     */
    private function loopIdArr()
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            if (!is_pos_int($id)) {
                throw new Exception('Bad id.');
            }
            $this->addToZip($id);
        }
        $this->addJson();
        $this->zip->close();
    }


    /*
     * Clean up the temporary files (csv, txt and pdf)
     *
     */
    public function __destruct()
    {
        foreach ($this->filesToDelete as $file) {
            unlink($file);
        }
    }
}
