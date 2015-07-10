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

class MakeCsv
{

    // the lines in the csv file
    private $list = array();
    private $idList;
    private $idArr = array();
    private $table;
    private $fileName;
    private $filePath;
    private $data;
    private $url;


    public function __construct($idList, $type, $path = null)
    {
        // assign and check id
        $this->idList = $idList;

        // assign and check type
        $this->table = $type;
        $this->checkType();

        // assign path
        $this->path = $path;

        $this->fileName = hash("sha512", uniqid(rand(), true)) . '.csv';
        $this->filePath = ELAB_ROOT . 'uploads/tmp/' . $this->fileName;

        $this->populateFirstLine();

        // main loop
        $this->loopIdArr();
    }
    /*
     * Return the full path of the file. The only public function.
     *
     * @return string The file path of the csv
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /*
     * Validate the type we have.
     *
     */
    private function checkType()
    {
        $correctValuesArr = array('experiments', 'items');
        if (!in_array($this->table, $correctValuesArr)) {
            throw new Exception('Bad type!');
        }
    }

    /*
     * Here we populate the first row: it will be the column names
     *
     */
    private function populateFirstLine()
    {
        if ($this->table === 'experiments') {
            $this->list[] = array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        } else {
            $this->list[] = array('title', 'description', 'id', 'date', 'type', 'rating', 'url');
        }
    }

    /*
     * Main loop
     *
     */
    private function loopIdArr()
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            if (!is_pos_int($id)) {
                throw new Exception('Bad id.');
            }
            $this->initData($id);
            $this->setUrl($id);
            $this->addLine();
        }
        $this->writeCsv();
    }

    /*
     * Get data about the item
     *
     */
    private function initData($id)
    {
        global $pdo;

        if ($this->table === 'experiments') {
            $sql = "SELECT experiments.*,
                status.name AS statusname
                FROM experiments
                LEFT JOIN status ON (experiments.status = status.id)
                WHERE experiments.id = :id";
        } else {
            $sql = "SELECT items.*,
                items_types.name AS typename
                FROM items
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE items.id = :id";
        }

        $req = $pdo->prepare($sql);
        $req->bindParam('id', $id, \PDO::PARAM_INT);
        $req->execute();
        $this->data = $req->fetch();
    }

    private function setUrl($id)
    {
        // Construct URL
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        $needle = array('make_csv.php', 'make_pdf.php', 'make_zip.php', 'app/timestamp.php');

        if ($this->table === 'experiments') {
            $url = str_replace($needle, 'experiments.php', $url);
        } else { //item
            $url = str_replace($needle, 'database.php', $url);
        }
        $this->url = $url . "?mode=view&id=" . $id;
    }

    private function addLine()
    {
        if ($this->table === 'experiments') {
            // populate
            $this->list[] = array(
                $this->data['id'],
                $this->data['date'],
                htmlspecialchars_decode($this->data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($this->data['body'], ENT_QUOTES | ENT_COMPAT))),
                htmlspecialchars_decode($this->data['statusname'], ENT_QUOTES | ENT_COMPAT),
                $this->data['elabid'],
                $this->url
            );
        } else {
            // populate
            $this->list[] = array(
                htmlspecialchars_decode($this->data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($this->data['body'], ENT_QUOTES | ENT_COMPAT))),
                $this->data['id'],
                $this->data['date'],
                htmlspecialchars_decode($this->data['typename'], ENT_QUOTES | ENT_COMPAT),
                $this->data['rating'],
                $this->url
            );
        }
    }

    private function writeCsv()
    {
        $fp = fopen($this->filePath, 'w+');
        // utf8 headers
        fwrite($fp, "\xEF\xBB\xBF");
        foreach ($this->list as $fields) {
                fputcsv($fp, $fields);
        }
        fclose($fp);
    }
}
