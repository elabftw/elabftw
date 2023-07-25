<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use function basename;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractTemplateEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Status;
use Elabftw\Models\Teams;
use Elabftw\Models\Uploads;
use function hash_file;
use function json_decode;
use League\Flysystem\UnableToReadFile;
use function sprintf;

/**
 * Import a .eln file.
 */
class Eln extends AbstractZip
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
        } catch (UnableToReadFile) {
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
        $createTarget = $this->targetNumber;
        $title = $dataset['name'] ?? _('Untitled');

        if ($this->Entity instanceof AbstractConcreteEntity) {
            if ($this->Entity instanceof Experiments) {
                // no template
                $createTarget = -1;
            }
            $this->Entity->setId($this->Entity->create($createTarget, array()));
        } elseif ($this->Entity instanceof AbstractTemplateEntity) {
            $this->Entity->setId($this->Entity->create($title));
        }
        $this->Entity->patch(Action::Update, array('title' => $title, 'bodyappend' => $dataset['text'] ?? ''));

        // TAGS
        if (isset($dataset['keywords'])) {
            foreach ($dataset['keywords'] as $tag) {
                $this->Entity->Tags->postAction(Action::Create, array('tag' => $tag));
            }
        }

        // LINKS
        if (isset($dataset['mentions']) && !empty($dataset['mentions'])) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach ($dataset['mentions'] as $link) {
                $linkHtml .= sprintf("<li><a href='%s'>%s</a></li>", $link['@id'], $link['name']);
            }
            $linkHtml .= '</ul>';
            $this->Entity->patch(Action::Update, array('bodyappend' => $linkHtml));
        }

        // COMMENTS
        if (isset($dataset['comment'])) {
            foreach ($dataset['comment'] as $comment) {
                $author = $comment['author'];
                if (is_string($comment['author'])) {
                    $author = $this->getNodeFromId($comment['author']);
                }
                $content = sprintf(
                    "Imported comment from %s %s (%s)\n\n%s",
                    $author['givenName'] ?? '',
                    $author['familyName'] ?? 'Unknown',
                    $comment['dateCreated'],
                    $comment['text'],
                );
                $this->Entity->Comments->postAction(Action::Create, array('comment' => $content));
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
                $this->Entity->patch(Action::Update, array('bodyappend' => $this->part2html($part)));
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
        // checksum is mandatory for import
        if (!isset($file['sha256']) || hash_file('sha256', $filepath) !== $file['sha256']) {
            throw new ImproperActionException(sprintf('Error during import: %s has incorrect sha256 sum.', basename($filepath)));
        }
        $newUploadId = $this->Entity->Uploads->create(new CreateUpload($file['name'] ?? basename($file['@id']), $filepath, $file['description'] ?? null));
        // the alternateName holds the previous long_name of the file
        if (isset($file['alternateName'])) {
            // read the newly created upload so we can get the new long_name to replace the old in the body
            $Uploads = new Uploads($this->Entity, $newUploadId);
            $currentBody = $this->Entity->readOne()['body'];
            $newBody = str_replace($file['alternateName'], $Uploads->uploadData['long_name'], $currentBody);
            $this->Entity->patch(Action::Update, array('body' => $newBody));
        }
        // special case for export-elabftw.json
        if (basename($filepath) === 'export-elabftw.json') {
            $fs = FsTools::getFs(dirname($filepath));
            $json = json_decode($fs->read(basename($filepath)), true, 512, JSON_THROW_ON_ERROR)[0];
            if ($this->Entity instanceof AbstractConcreteEntity) {
                // rating
                $this->Entity->patch(Action::Update, array('rating' => $json['rating'] ?? ''));
                // adjust the date
                $this->Entity->patch(Action::Update, array('date' => $json['date']));
                if ($this->Entity instanceof Experiments) {
                    // try and adjust the status for experiments
                    $sourceStatus = $json['category'];
                    // let's see if we can find a status like this in target instance
                    $targetStatusArr = (new Status(new Teams($this->Users, $this->Users->userData['team'])))->readAll();
                    $filteredStatus = array_filter($targetStatusArr, function ($status) use ($sourceStatus) {
                        return $status['category'] === $sourceStatus;
                    });
                    if (!empty($filteredStatus)) {
                        // use array_key_first because the filter will not reset the key numbering
                        $this->Entity->patch(Action::Update, array('category' => (string) $filteredStatus[array_key_first($filteredStatus)]['category_id']));
                    }
                }
            }
            if ($json['metadata'] !== null) {
                $this->Entity->patch(Action::Update, array('metadata' => json_encode($json['metadata'], JSON_THROW_ON_ERROR, 512)));
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

    private function part2html(array $part): string
    {
        $html = sprintf('<p>%s<br>%s', $part['name'] ?? '', $part['dateCreated'] ?? '');
        $html .= '<ul>';
        foreach ($part['hasPart'] as $subpart) {
            $html .= '<li>' . basename($subpart['@id']) . ' ' . ($subpart['description'] ?? '') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
