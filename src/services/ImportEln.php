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
        // I believe this is a bug in phpstan. Using directly new Experiements() is ok but not the factory for some reason.
        // Might also be a bug in elab, not sure where it is FIXME
        // @phpstan-ignore-next-line
        $id = $Entity->create(new EntityParams((string) $this->target));
        $Entity->setId($id);
        $Entity->update(new EntityParams($dataset['name'] ?? _('Untitled'), 'title'));
        $Entity->update(new EntityParams($dataset['text'] ?? '', 'bodyappend'));
        // tags are stored in the 'keywords' property
        foreach ($dataset['keywords'] as $tag) {
            $Entity->Tags->create(new TagParams($tag));
        }
        // links are in the 'mentions' property as remote ids
        if ($dataset['mentions']) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach ($dataset['mentions'] as $link) {
                $linkHtml .= sprintf("<li><a href='%s'>%s</a></li>", $link['@id'], $link['name']);
            }
            $linkHtml .= '</ul>';
            $Entity->update(new EntityParams($linkHtml, 'bodyappend'));
        }

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
            // TODO here handle sub datasets as linked entries
            foreach ($part['hasPart'] as $subpart) {
                if ($subpart['@type'] === 'File') {
                    $this->importFile($subpart, $Entity);
                }
            }
            break;
        case 'File':
            if (str_starts_with($part['@id'], 'http')) {
                // we don't import remote files
                return;
            }
            $this->importFile($part, $Entity);
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
        // special case for export-elabftw.json
        if (basename($filepath) === 'export-elabftw.json') {
            $json = json_decode(file_get_contents($filepath), true, 512, JSON_THROW_ON_ERROR)[0];
            $Entity->update(new EntityParams($json['rating'], 'rating'));
            if ($json['metadata'] !== null) {
                $Entity->update(new EntityParams(json_encode($json['metadata'], JSON_THROW_ON_ERROR, 512), 'metadata'));
            }
            // add steps
            if (!empty($json['steps'])) {
                foreach ($json['steps'] as $step) {
                    $Entity->Steps->import($step);
                }
            }
            // TODO handle links: linked items should be included as datasets in the .eln, with a relationship to the main entry, and they should be imported as links
        }
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
}
