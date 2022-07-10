<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function basename;
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
use function hash_file;
use function json_decode;
use League\Flysystem\FilesystemOperator;
use function sprintf;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Import a .eln file
 */
class ImportEln extends AbstractImport
{
    private AbstractEntity $Entity;

    // path where we extract the archive content (subfolder of cache/elab)
    private string $tmpPath;

    // path where the metadata.json file lives (first folder found in archive)
    private string $root;

    // complete graph: all nodes from metadata json
    private array $graph;

    // the folder name where we extract the archive
    private string $tmpDir;

    // userid for experiments, category for items, for templates we don't care about the id (0 is sent anyway)
    private int $targetNumber;

    /**
     * The $target will be userid_X or category_X or templates_X
     */
    public function __construct(Users $users, string $target, string $canread, string $canwrite, UploadedFile $uploadedFile, private FilesystemOperator $fs)
    {
        $this->targetNumber = (int) explode('_', $target)[1];
        $entityType = AbstractEntity::TYPE_ITEMS;
        if (str_starts_with($target, 'userid')) {
            // check that we can import stuff in experiments of target user
            if ($this->targetNumber !== (int) $users->userData['userid'] && $users->isAdminOf($this->targetNumber) === false) {
                throw new IllegalActionException('User tried to import archive in experiments of a user but they are not admin of that user');
            }
            $entityType = AbstractEntity::TYPE_EXPERIMENTS;
            $users = new Users($this->targetNumber, $users->userData['team']);
        }
        // we try to import a template
        if (str_starts_with($target, 'templates')) {
            $entityType = AbstractEntity::TYPE_TEMPLATES;
        }
        // TODO check the category is in our team
        parent::__construct($users, $this->targetNumber, $canread, $canwrite, $uploadedFile);
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
        // start by extracting the archive to the temporary folder
        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);

        // figure out the path to the root of the eln (where the metadata file lives)
        // the name of the folder is not fixed, so list folders and pick the first one found (there should be only one)
        $listing = $this->fs->listContents($this->tmpDir);
        foreach ($listing as $item) {
            if ($item instanceof \League\Flysystem\DirectoryAttributes) {
                $this->root = $item->path();
                break;
            }
        }

        // now read the metadata json file
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
        $createTarget = (string) $this->targetNumber;
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
        if ($dataset['keywords']) {
            foreach ($dataset['keywords'] as $tag) {
                $this->Entity->Tags->create(new TagParams($tag));
            }
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
        if ($dataset['comment']) {
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
            $this->Entity->update(new EntityParams($json['rating'] ?? '', 'rating'));
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
