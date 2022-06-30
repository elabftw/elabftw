<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\TagParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Traits\EntityTrait;
use Elabftw\Traits\UploadTrait;
use function json_decode;
use League\Flysystem\FilesystemOperator;
use function mb_strlen;
use PDO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Import a .elabftw.zip file into the database.
 */
class ImportZip extends AbstractImport
{
    use EntityTrait;
    use UploadTrait;

    // number of items we got into the database
    public int $inserted = 0;

    /** @var AbstractEntity $Entity instance of Entity */
    private $Entity;

    private string $tmpPath;

    // the folder where we extract the zip
    private string $tmpDir;

    // experiments or items
    private string $type = 'experiments';

    public function __construct(Users $users, int $target, string $canread, string $canwrite, UploadedFile $uploadedFile, private FilesystemOperator $fs)
    {
        parent::__construct($users, $target, $canread, $canwrite, $uploadedFile);
        $this->Entity = new Items($users);
        // set up a temporary directory in the cache to extract the zip to
        $this->tmpDir = FsTools::getUniqueString();
        $this->tmpPath = FsTools::getCacheFolder('elab') . '/' . $this->tmpDir;
    }

    /**
     * Cleanup: remove the temporary folder created
     */
    public function __destruct()
    {
        $this->fs->deleteDirectory($this->tmpDir);
    }

    /**
     * Do the import
     * We get all the info we need from the embedded .json file
     */
    public function import(): void
    {
        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);

        $file = '/.elabftw.json';
        $content = $this->fs->read($this->tmpDir . $file);
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (isset($json[0]['team'])) {
            $this->type = 'items';
        }
        $this->importAll($json);
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
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, canread, canwrite, elabid, metadata)
            VALUES(:team, :title, :date, :body, :userid, :category, :canread, :canwrite, :elabid, :metadata)';

        if ($this->type === 'experiments') {
            $sql = 'INSERT into experiments(title, date, body, userid, canread, canwrite, category, elabid, metadata)
                VALUES(:title, :date, :body, :userid, :canread, :canwrite, :category, :elabid, :metadata)';
        }

        // make sure there is an elabid (might not exist for items before v4.0)
        $elabid = $item['elabid'] ?? $this->generateElabid();

        $req = $this->Db->prepare($sql);
        if ($this->type === 'items') {
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        }
        $req->bindParam(':title', $item['title']);
        $req->bindParam(':date', $item['date']);
        $req->bindParam(':body', $item['body']);
        $req->bindValue(':canread', $this->canread);
        $req->bindValue(':canwrite', $this->canwrite);
        $req->bindParam(':elabid', $elabid);
        $req->bindParam(':metadata', $item['metadata']);
        if ($this->type === 'items') {
            $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':category', $this->target, PDO::PARAM_INT);
        } else {
            $req->bindValue(':category', $this->getDefaultStatus());
            $req->bindParam(':userid', $this->target, PDO::PARAM_INT);
        }

        $this->Db->execute($req);

        $newItemId = $this->Db->lastInsertId();

        // create necessary objects
        if ($this->type === 'experiments') {
            $this->Entity = new Experiments($this->Users, $newItemId);
        } else {
            $this->Entity->setId($newItemId);
        }

        // add tags
        if (mb_strlen($item['tags'] ?? '') > 1) {
            $this->tagsDbInsert($item['tags']);
        }
        // add links
        if (!empty($item['links'])) {
            // don't import the links as is because the id might be different from the one we had before
            // so add the link in the body
            $header = '<h3>Linked items:</h3><ul>';
            $end = '</ul>';
            $linkText = '';
            foreach ($item['links'] as $link) {
                $linkText .= sprintf('<li>[%s] %s</li>', $link['name'], $link['title']);
            }
            $params = new EntityParams($item['title'], 'title');
            $this->Entity->update($params);
            $params = new EntityParams($item['date'], 'date');
            $this->Entity->update($params);
            $params = new EntityParams($item['body'] . $header . $linkText . $end, 'body');
            $this->Entity->update($params);
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
            $this->Entity->Tags->create(new TagParams($tag));
        }
    }

    /**
     * Loop the json and import the items.
     */
    private function importAll(array $json): void
    {
        foreach ($json as $item) {
            $this->dbInsert($item);

            // upload the attached files
            if (is_array($item['uploads'])) {
                $titlePath = Filter::forFilesystem($item['title']);
                $shortElabid = Tools::getShortElabid($item['elabid']);
                foreach ($item['uploads'] as $file) {
                    if ($this->type === 'experiments') {
                        $filePath = $this->tmpPath . '/' .
                            $item['date'] . ' - ' . $titlePath . ' - ' . $shortElabid . '/' . $file['real_name'];
                    } else {
                        $filePath = $this->tmpPath . '/' .
                            $item['category'] . ' - ' . $titlePath . ' - ' . $shortElabid . '/' . $file['real_name'];
                    }

                    /**
                     * Ok so right now if you have several files with the same name, the real_name in the json will be
                     * the same, but the extracted file will have a 1_ in front of the name. So here we will skip the
                     * import but this should be handled. One day. Maybe.
                     */
                    if (!is_readable($filePath)) {
                        throw new ImproperActionException(sprintf('Tried to import a file but it was not present in the zip archive: %s.', basename($filePath)));
                    }
                    $this->Entity->Uploads->create(new CreateUpload(basename($filePath), $filePath, $file['comment']));
                }
            }
            ++$this->inserted;
        }
    }
}
