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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use function hash_file;
use function json_decode;
use League\Flysystem\UnableToReadFile;
use function sprintf;

/**
 * Import a .eln file.
 */
class ImportEln extends AbstractImportZip
{
    // path where the metadata.json file lives (first folder found in archive)
    private string $root;

    // complete graph: all nodes from metadata json
    private array $graph;

    /**
     * Do the import
     */
    public function import(): void
    {
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
        try {
            $content = $this->fs->read($this->root . $file);
        } catch (UnableToReadFile $e) {
            throw new ImproperActionException(sprintf(_('Error: could not read archive file properly! (missing %s)'), $file));
        }
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
        if (isset($dataset['keywords'])) {
            foreach ($dataset['keywords'] as $tag) {
                $this->Entity->Tags->create(new TagParams($tag));
            }
        }

        // LINKS
        if (isset($dataset['mentions']) && !empty($dataset['mentions'])) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach ($dataset['mentions'] as $link) {
                $linkHtml .= sprintf("<li><a href='%s'>%s</a></li>", $link['@id'], $link['name']);
            }
            $linkHtml .= '</ul>';
            $this->Entity->update(new EntityParams($linkHtml, 'bodyappend'));
        }

        // COMMENTS
        if (isset($dataset['comment'])) {
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
