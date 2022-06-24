<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
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
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Traits\UploadTrait;
use function json_decode;
use League\Flysystem\FilesystemOperator;
use function mb_strlen;
use PDO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Import a .eln file
 */
class ImportEln extends AbstractImport
{
    use UploadTrait;

    // number of items we got into the database
    public int $inserted = 0;

    private AbstractEntity $Entity;

    private string $tmpPath;

    private string $root;

    private array $graph;

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
     */
    public function import(): void
    {
        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);

        $listing = $this->fs->listContents($this->tmpDir);
        $root = '';
        foreach ($listing as $item) {
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $this->root = $item->path();
                break;
            }
        }
        $file = '/ro-crate-metadata.json';
        $content = $this->fs->read($this->root . $file);
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->graph = $json['@graph'];
        // find the node describing the crate
        foreach ($json['@graph'] as $node) {
            if ($node['@id'] === './') {
                // loop over each hasPart of the root node
                foreach ($node['hasPart'] as $part) {
                    $this->importRootDataset($this->getNodeFromId($part['@id']));
                }
            }
        }
    }

    private function getNodeFromId(string $id): array
    {
        foreach ($this->graph as $node) {
            if ($node['@id'] === $id) {
                return $node;
            }
        }
        return array();
    }

    private function importRootDataset(array $dataset): void
    {
        $Entity = (new EntityFactory($this->Users, AbstractEntity::TYPE_ITEMS))->getEntity();
        $id = $Entity->create(new EntityParams((string) $this->target));
        $Entity->setId($id);
        $Entity->update(new EntityParams($dataset['name'] ?? _('Untitled'), 'title'));
        $Entity->update(new EntityParams($dataset['description'] ?? '', 'bodyappend'));
        $this->inserted++;
        // now loop over the parts of this node to find the rest of the files
        // the getNodeFromId might return nothing but that's okay, we just continue to try and find stuff
        foreach ($dataset['hasPart'] as $part) {
            $this->importPart($this->getNodeFromId($part['@id']), $Entity);
        }
    }

    private function importPart(array $part, AbstractEntity $Entity): void
    {
        if (!isset($part['@type'])) {
            return;
        }

        switch($part['@type']) {
        case 'Dataset':
            $Entity->update(new EntityParams($this->part2html($part), 'bodyappend'));
            foreach ($part['hasPart'] as $subpart) {
                if (($subpart['@type'] ?? 'nope') === 'File') {
                    $this->importFile($subpart, $Entity);
                }
            }
            break;
        case 'File':
            if (!str_starts_with($part['@id'], 'http')) {
                $this->importFile($part, $Entity);
            }
            break;
        default:
            return;
        }
    }

    private function importFile(array $file, AbstractEntity $Entity): void
    {
        // note: path transversal vuln is detected and handled by flysystem
        $filepath = $this->tmpPath . '/' . basename($this->root) . '/' . $file['@id'];
        if (isset($file['sha256'])) {
            $this->checksum($filepath, $file['sha256']);
        }
        $Entity->Uploads->create(new CreateUpload($file['name'] ?? basename($file['@id']), $filepath, $file['description'] ?? null));
    }

    private function checksum(string $filepath, string $sha256sum): void
    {
        if (hash_file('sha256', $filepath) !== $sha256sum) {
            throw new ImproperActionException(sprintf('Error during import: %s has incorrect sha256 sum.', basename($filepath)));
        }
    }

    private function part2html(array $part): string
    {
        $html = sprintf('<p>%s<br>%s', $part['name'] ?? '', $part['dateCreated'] ?? '');
        $html .= '<ul>';
        foreach ($part['hasPart'] as $subpart) {
            $html .= '<li>' . basename($subpart['@id']) . ' ' . ($subpart['description'] ?? '') . '</li>';
        }
        return $html .= '</ul>';
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
        $elabid = $item['elabid'] ?? Tools::generateElabid();

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
}
