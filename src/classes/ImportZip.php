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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

/**
 * Import a .elabftw.zip file into the database.
 */
class ImportZip extends AbstractImport
{
    /** @var AbstractEntity $Entity instance of Entity */
    private $Entity;

    /** @var int $inserted number of item we have inserted */
    public $inserted = 0;

    /** @var string $tmpPath the folder where we extract the zip */
    private $tmpPath;

    /** @var array $json an array with the data we want to import */
    private $json;

    /** @var string $type experiments or items */
    private $type = 'items';

    /**
     * Constructor
     *
     * @param Users $users instance of Users
     * @param Request $request instance of Request
     * @return void
     */
    public function __construct(Users $users, Request $request)
    {
        parent::__construct($users, $request);

        $this->target = $this->getTarget();
        // this is where we will extract the zip
        $this->tmpPath = \dirname(__DIR__, 2) . '/cache/elab/' . \uniqid((string) \mt_rand(), true);
        if (!is_dir($this->tmpPath) && !mkdir($this->tmpPath, 0700, true) && !is_dir($this->tmpPath)) {
            throw new RuntimeException('Unable to create temporary folder! (' . $this->tmpPath . ')');
        }

        $this->openFile();
        $this->readJson();
        $this->importAll();
    }

    /**
     * We get all the info we need from the embedded .json file
     *
     * @return void
     */
    private function readJson(): void
    {
        $file = $this->tmpPath . "/.elabftw.json";
        $content = file_get_contents($file);
        $this->json = json_decode($content, true);
        if (isset($this->json[0]['elabid'])) {
            $this->type = 'experiments';
        }
    }

    /**
     * Select a status for our experiments.
     *
     * @return int The default status ID of the team
     */
    private function getDefaultStatus(): int
    {
        $sql = 'SELECT id FROM status WHERE team = :team AND is_default = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * The main SQL to create a new item with the title and body we have
     *
     * @param array $item the item to insert
     * @throws Exception if SQL request failed
     * @return void
     */
    private function dbInsert($item): void
    {
        $sql = "INSERT INTO items(team, title, date, body, userid, type)
            VALUES(:team, :title, :date, :body, :userid, :type)";

        if ($this->type === 'experiments') {
            $sql = "INSERT into experiments(team, title, date, body, userid, visibility, status, elabid)
                VALUES(:team, :title, :date, :body, :userid, :visibility, :status, :elabid)";
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->bindParam(':title', $item['title']);
        $req->bindParam(':date', $item['date']);
        $req->bindParam(':body', $item['body']);
        if ($this->type === 'items') {
            $req->bindParam(':userid', $this->Users->userid);
            $req->bindParam(':type', $this->target);
        } else {
            $req->bindValue(':visibility', 'team');
            $req->bindValue(':status', $this->getDefaultStatus());
            $req->bindParam(':userid', $this->target);
            $req->bindParam(':elabid', $item['elabid']);
        }


        if (!$req->execute()) {
            throw new Exception('Cannot import in database!');
        }

        $newItemId = $this->Db->lastInsertId();

        // create necessary objects
        if ($this->type === 'experiments') {
            $this->Entity = new Experiments($this->Users, $newItemId);
        } else {
            $this->Entity = new Database($this->Users, $newItemId);
        }

        if (\mb_strlen($item['tags']) > 1) {
            $this->tagsDbInsert($item['tags']);
        }
    }

    /**
     * Loop over the tags and insert them for the new entity
     *
     * @param string $tags the tags string separated by '|'
     * @return void
     */
    private function tagsDbInsert($tags): void
    {
        $tagsArr = explode('|', $tags);
        foreach ($tagsArr as $tag) {
            $this->Entity->Tags->create($tag);
        }
    }

    /**
     * Loop the json and import the items.
     *
     * @return void
     */
    private function importAll(): void
    {
        foreach ($this->json as $item) {
            $this->dbInsert($item);

            // upload the attached files
            if (is_array($item['uploads'])) {
                $titlePath = preg_replace('/[^A-Za-z0-9]/', '_', $item['title']);
                foreach ($item['uploads'] as $file) {
                    if ($this->type === 'experiments') {
                        $filePath = $this->tmpPath . '/' .
                            $item['date'] . '-' . $titlePath . '/' . $file['real_name'];
                    } else {
                        $filePath = $this->tmpPath . '/' .
                            $item['category'] . ' - ' . $titlePath . '/' . $file['real_name'];
                    }

                    /**
                     * Ok so right now if you have several files with the same name, the real_name in the json will be
                     * the same, but the extracted file will have a 1_ in front of the name. So here we will skip the
                     * import but this should be handled. One day. Maybe.
                     */
                    if (is_readable($filePath)) {
                        $this->Entity->Uploads->createFromLocalFile($filePath, $file['comment']);
                    }
                }
            }
            ++$this->inserted;
        }
    }

    /**
     * Extract the zip to the temporary folder
     *
     * @throws Exception if it cannot open the zip
     * @return void
     */
    protected function openFile(): void
    {
        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname()) && $Zip->extractTo($this->tmpPath);
    }

    /**
     * Cleanup : remove the temporary folder created
     */
    public function __destruct()
    {
        // first remove content
        $di = new RecursiveDirectoryIterator($this->tmpPath, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
        // and remove folder itself
        rmdir($this->tmpPath);
    }
}
