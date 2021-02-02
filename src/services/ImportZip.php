<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

/**
 * Import a .elabftw.zip file into the database.
 */
class ImportZip extends AbstractImport
{
    // number of items we got into the database
    public int $inserted = 0;

    /** @var AbstractEntity $Entity instance of Entity */
    private $Entity;

    // the folder where we extract the zip
    private string $tmpPath = '';

    // an array with the data we want to import
    private array $json = array();

    // experiments or items
    private string $type = 'items';

    /**
     * Constructor
     *
     * @param Users $users instance of Users
     * @param Request $request instance of Request
     * @throws ImproperActionException
     * @return void
     */
    public function __construct(Users $users, Request $request)
    {
        parent::__construct($users, $request);
        $this->Entity = new Database($users);
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
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        // and remove folder itself
        rmdir($this->tmpPath);
    }

    /**
     * Do the import
     */
    public function import(): void
    {
        // this is where we will extract the zip
        $this->tmpPath = \dirname(__DIR__, 2) . '/cache/elab/' . \bin2hex(\random_bytes(16));
        if (!is_dir($this->tmpPath) && !mkdir($this->tmpPath, 0700, true) && !is_dir($this->tmpPath)) {
            throw new ImproperActionException('Unable to create temporary folder! (' . $this->tmpPath . ')');
        }

        $this->openFile();
        $this->readJson();
        $this->importAll();
    }

    /**
     * Extract the zip to the temporary folder
     */
    private function openFile(): void
    {
        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);
    }

    /**
     * We get all the info we need from the embedded .json file
     */
    private function readJson(): void
    {
        $file = $this->tmpPath . '/.elabftw.json';
        $content = file_get_contents($file);
        if ($content === false) {
            throw new ImproperActionException('Unable to read the json file!');
        }
        $this->json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
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
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * The main SQL to create a new item with the title and body we have
     *
     * @param array<string, mixed> $item the item to insert
     * @throws ImproperActionException
     */
    private function dbInsert($item): void
    {
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, canread)
            VALUES(:team, :title, :date, :body, :userid, :category, :canread)';

        if ($this->type === 'experiments') {
            $sql = 'INSERT into experiments(title, date, body, userid, canread, category, elabid)
                VALUES(:title, :date, :body, :userid, :canread, :category, :elabid)';
        }
        $req = $this->Db->prepare($sql);
        if ($this->type !== 'experiments') {
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        }
        $req->bindParam(':title', $item['title']);
        $req->bindParam(':date', $item['date']);
        $req->bindParam(':body', $item['body']);
        $req->bindValue(':canread', $this->canread);
        if ($this->type === 'items') {
            $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':category', $this->target, PDO::PARAM_INT);
        } else {
            $req->bindValue(':category', $this->getDefaultStatus());
            $req->bindParam(':userid', $this->target, PDO::PARAM_INT);
            $req->bindParam(':elabid', $item['elabid']);
        }

        if (!$req->execute()) {
            throw new ImproperActionException('Cannot import in database!');
        }

        $newItemId = $this->Db->lastInsertId();

        // create necessary objects
        if ($this->type === 'experiments') {
            $this->Entity = new Experiments($this->Users, $newItemId);
        } else {
            $this->Entity->setId($newItemId);
        }

        // add tags
        if (\mb_strlen($item['tags'] ?? '') > 1) {
            $this->tagsDbInsert($item['tags']);
        }
        // add links
        if (!empty($item['links'])) {
            // don't import the links as as because the id might be different from the one we had before
            // so add the link in the body
            $header = '<h3>Linked items:</h3><ul>';
            $end = '</ul>';
            $linkText = '';
            foreach ($item['links'] as $link) {
                $linkText .= sprintf('<li>[%s] %s</li>', $link['name'], $link['title']);
            }
            $this->Entity->update($item['title'], $item['date'], $item['body'] . $header . $linkText . $end);
        }
        // add steps
        if (!empty($item['steps'])) {
            foreach ($item['steps'] as $step) {
                $this->Entity->Steps->import($step);
            }
        }
    }

    /**
     * Loop over the tags and insert them for the new entity
     *
     * @param string $tags the tags string separated by '|'
     */
    private function tagsDbInsert($tags): void
    {
        $tagsArr = explode('|', $tags);
        foreach ($tagsArr as $tag) {
            $this->Entity->Tags->create(new ParamsProcessor(array('tag' => $tag)));
        }
    }

    /**
     * Loop the json and import the items.
     */
    private function importAll(): void
    {
        foreach ($this->json as $item) {
            $this->dbInsert($item);

            // upload the attached files
            if (is_array($item['uploads'])) {
                $titlePath = Filter::forFilesystem($item['title']);
                foreach ($item['uploads'] as $file) {
                    if ($this->type === 'experiments') {
                        $filePath = $this->tmpPath . '/' .
                            $item['date'] . ' - ' . $titlePath . '/' . $file['real_name'];
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
}
