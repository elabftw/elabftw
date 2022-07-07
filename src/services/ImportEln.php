<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\TagParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Traits\UploadTrait;
use function json_decode;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Import a .eln file
 */
class ImportEln extends AbstractImport
{
    use UploadTrait;

    // final number of items imported
    public int $inserted = 0;

    private AbstractEntity $Entity;

    private string $tmpPath;

    private string $root;

    private array $graph;

    // the folder where we extract the archive
    private string $tmpDir;

    private int $categoryOrUserid;

    public function __construct(Users $users, string $target, string $canread, string $canwrite, UploadedFile $uploadedFile, private FilesystemOperator $fs)
    {
        $this->categoryOrUserid = (int) explode('_', $target)[1];
        $entityType = AbstractEntity::TYPE_ITEMS;
        if (str_starts_with($target, 'userid')) {
            // check that we can import stuff in experiments of target user
            if ($this->categoryOrUserid !== (int) $users->userData['userid'] && $users->isAdminOf($this->categoryOrUserid) === false) {
                throw new IllegalActionException('User tried to import archive in experiments of a user but they are not admin of that user');
            }
            $entityType = AbstractEntity::TYPE_EXPERIMENTS;
            $users = new Users($this->categoryOrUserid, $users->userData['team']);
        }
        // TODO check the category is in our team
        parent::__construct($users, $this->categoryOrUserid, $canread, $canwrite, $uploadedFile);
        $this->Entity = (new EntityFactory($users, $entityType))->getEntity();
        // set up a temporary directory in the cache to extract the archive to
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
        $createTarget = (string) $this->categoryOrUserid;
        if ($this->Entity instanceof Experiments) {
            // no template
            $createTarget = '-1';
        }
        // I believe this is a bug in phpstan. Using directly new Experiements() is ok but not the factory for some reason.
        // Might also be a bug in elab, not sure where it is FIXME
        // @phpstan-ignore-next-line
        $id = $this->Entity->create(new EntityParams($createTarget));
        $this->Entity->setId($id);
        $this->Entity->update(new EntityParams($dataset['name'] ?? _('Untitled'), 'title'));
        $this->Entity->update(new EntityParams($dataset['text'] ?? '', 'bodyappend'));

        // TAGS
        foreach ($dataset['keywords'] as $tag) {
            $this->Entity->Tags->create(new TagParams($tag));
        }

        // LINKS
        if ($dataset['mentions']) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach ($dataset['mentions'] as $link) {
                $linkHtml .= sprintf("<li><a href='%s'>%s</a></li>", $link['@id'], $link['name']);
            }
            $linkHtml .= '</ul>';
            $this->Entity->update(new EntityParams($linkHtml, 'bodyappend'));
        }

        // COMMENTS
        foreach ($dataset['comment'] as $comment) {
            $content = sprintf(
                "Imported comment from %s %s (%s)\n\n%s",
                $comment['author']['firstname'] ?? '',
                $comment['author']['lastname'] ?? 'Unknown',
                $comment['dateCreated'],
                $comment['text'],
            );
            $this->Entity->Comments->create(new ContentParams($content));
        }

        $this->inserted++;
        // now loop over the parts of this node to find the rest of the files
        // the getNodeFromId might return nothing but that's okay, we just continue to try and find stuff
        foreach ($dataset['hasPart'] as $part) {
            $this->importPart($this->getNodeFromId($part['@id']));
        }
    }

    private function importPart(array $part): void
    {
        if (!isset($part['@type'])) {
            return;
        }

        switch ($part['@type']) {
        case 'Dataset':
            $this->Entity->update(new EntityParams($this->part2html($part), 'bodyappend'));
            // TODO here handle sub datasets as linked entries
            foreach ($part['hasPart'] as $subpart) {
                if ($subpart['@type'] === 'File') {
                    $this->importFile($subpart);
                }
            }
            break;
        case 'File':
            if (str_starts_with($part['@id'], 'http')) {
                // we don't import remote files
                return;
            }
            $this->importFile($part);
            break;
        default:
            return;
        }
    }

    private function importFile(array $file): void
    {
        // note: path transversal vuln is detected and handled by flysystem
        $filepath = $this->tmpPath . '/' . basename($this->root) . '/' . $file['@id'];
        if (isset($file['sha256'])) {
            $this->checksum($filepath, $file['sha256']);
        }
        $this->Entity->Uploads->create(new CreateUpload($file['name'] ?? basename($file['@id']), $filepath, $file['description'] ?? null));
        // special case for export-elabftw.json
        if (basename($filepath) === 'export-elabftw.json') {
            $fs = FsTools::getFs(dirname($filepath));
            $json = json_decode($fs->read(basename($filepath)), true, 512, JSON_THROW_ON_ERROR)[0];
            $this->Entity->update(new EntityParams($json['rating'], 'rating'));
            if ($json['metadata'] !== null) {
                $this->Entity->update(new EntityParams(json_encode($json['metadata'], JSON_THROW_ON_ERROR, 512), 'metadata'));
            }
            // add steps
            if (!empty($json['steps'])) {
                foreach ($json['steps'] as $step) {
                    $this->Entity->Steps->import($step);
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
}
