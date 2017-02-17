<?php
/**
 * \Elabftw\Elabftw\ImportZip
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;

/**
 * Import a .elabftw.zip file into the database.
 */
class ImportZip extends Import
{
    /** pdo object */
    private $pdo;

    /** number of item we have inserted */
    public $inserted = 0;
    /** the folder where we extract the zip */
    private $tmpPath;
    /** an array with the data we want to import */
    private $json;

    /** the target item type */
    private $target;
    /** title of new item */
    private $title;
    /** body of new item */
    private $body;

    /** experiments or items */
    private $type;

    /** date of the item we import */
    private $date;

    /** unique identifier of the experiment */
    private $elabid;

    /**
     * the newly created id of the imported item
     * we need it for linking attached file(s) to the the new item
     */
    private $newItemId;

    /**
     * Constructor
     *
     */
    public function __construct()
    {

        $this->pdo = Db::getConnection();

        $this->checkFileReadable();
        $this->checkMimeType();
        $this->target = $this->getTarget();
        // this is where we will extract the zip
        $this->tmpPath = ELAB_ROOT . 'uploads/tmp/' . uniqid();
        if (!mkdir($this->tmpPath)) {
            throw new Exception('Cannot create temporary folder');
        }

        $this->openFile();
        $this->readJson();
        $this->importAll();
    }

    /**
     * Extract the zip to the temporary folder
     *
     * @throws Exception if it cannot open the zip
     * @return bool
     */
    protected function openFile()
    {
        $zip = new ZipArchive;
        return $zip->open($this->getFilePath()) && $zip->extractTo($this->tmpPath);
    }

    /**
     * We get all the info we need from the embedded .json file
     *
     */
    private function readJson()
    {
        $file = $this->tmpPath . "/.elabftw.json";
        $content = file_get_contents($file);
        $this->json = json_decode($content, true);
        $this->type = $this->json[0]['type'];
    }

    /**
     * Select a status for our experiments.
     *
     * @return int The default status of the team
     */
    private function getDefaultStatus()
    {
        $sql = 'SELECT id FROM status WHERE team = :team AND is_default = 1';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        return $req->fetchColumn();
    }


    /**
     * The main SQL to create a new item with the title and body we have
     *
     * @throws Exception if SQL request failed
     */
    private function dbInsert()
    {
        $sql = "INSERT INTO items(team, title, date, body, userid, type)
            VALUES(:team, :title, :date, :body, :userid, :type)";

        if ($this->type === 'experiments') {
            $sql = "INSERT into experiments(team, title, date, body, userid, visibility, status, elabid)
                VALUES(:team, :title, :date, :body, :userid, :visibility, :status, :elabid)";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id'], \PDO::PARAM_INT);
        $req->bindParam(':title', $this->title);
        $req->bindParam(':date', $this->date);
        $req->bindParam(':body', $this->body);
        if ($this->type === 'items') {
            $req->bindParam(':userid', $_SESSION['userid'], \PDO::PARAM_INT);
            $req->bindParam(':type', $this->target);
        } else {
            $req->bindValue(':visibility', 'team');
            $req->bindValue(':status', $this->getDefaultStatus());
            $req->bindParam(':userid', $this->target, \PDO::PARAM_INT);
            $req->bindParam(':elabid', $this->elabid);
        }


        if (!$req->execute()) {
            throw new Exception('Cannot import in database!');
        }
        // needed in importFile()
        $this->newItemId = $this->pdo->lastInsertId();
    }

    /**
     * If files are attached we want them!
     *
     * @throws Exception in case of error
     * @param string $file The path of the file in the archive
     */
    private function importFile($file)
    {
        $Users = new Users($_SESSION['userid']);
        if ($this->type === 'experiments') {
            $Entity = new Experiments($Users, $this->newItemId);
        } else {
            $Entity = new Database($Users, $this->newItemId);
        }
        $Upload = new Uploads($Entity);
        $Upload->createFromLocalFile($this->tmpPath . '/' . $file);
    }

    /**
     * Loop the json and import the items.
     *
     */
    private function importAll()
    {
        foreach ($this->json as $item) {

            $this->title = $item['title'];
            $this->body = $item['body'];
            $this->date = $item['date'];
            $this->elabid = $item['elabid'];
            $this->dbInsert();
            if (is_array($item['files'])) {
                foreach ($item['files'] as $file) {
                    $this->importFile($file);
                }
            }
            $this->inserted += 1;
        }
    }

    /**
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
