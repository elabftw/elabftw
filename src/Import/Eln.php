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

use DateTimeImmutable;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractTemplateEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsStatus;
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

        // Do we need to update data: don't sanitize input, escape output
        if ($this->graph[1]['@id'] === '#ro-crate_created'
            && version_compare($this->graph[1]['instrument']['version'], '5.0.0-alpha4', '>=')
        ) {
            $this->switchToEscapeOutput = false;
        }

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
        if ($this->switchToEscapeOutput) {
            $dataset['name'] = Tools::dontFilterInputEscapeOutput($dataset['name']);
        }
        $title = $dataset['name'] ?? _('Untitled');

        if ($this->Entity instanceof AbstractConcreteEntity) {
            if ($this->Entity instanceof Experiments) {
                // no template
                $createTarget = -1;
            }
            $this->Entity->setId($this->Entity->create($createTarget, array()));
            // set the date if we can
            $date = date('Y-m-d');
            if (isset($dataset['dateCreated'])) {
                $dateCreated = new DateTimeImmutable($dataset['dateCreated']);
                $date = $dateCreated->format('Y-m-d');
            }
            $this->Entity->patch(Action::Update, array('date' => $date));
        } elseif ($this->Entity instanceof AbstractTemplateEntity) {
            $this->Entity->setId($this->Entity->create($title));
        }
        $this->Entity->patch(Action::Update, array('title' => $title, 'bodyappend' => $dataset['text'] ?? ''));

        // TAGS: should normally be a comma separated string, but we allow array for BC
        if (isset($dataset['keywords'])) {
            $tags = $dataset['keywords'];
            if (is_string($dataset['keywords'])) {
                $tags = explode(',', $dataset['keywords']);
            }
            foreach ($tags as $tag) {
                if (!empty($tag)) {
                    if ($this->switchToEscapeOutput) {
                        $tag = Tools::dontFilterInputEscapeOutput($tag);
                    }
                    $this->Entity->Tags->postAction(Action::Create, array('tag' => $tag));
                }
            }
        }

        // LINKS
        if (isset($dataset['mentions']) && !empty($dataset['mentions'])) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach($dataset['mentions'] as $mention) {
                // for backward compatibility with elabftw's .eln from before 4.9, the "mention" attribute MAY contain all, instead of just being a link with an @id
                $fullMention = $mention;
                // after 4.9 the "mention" attribute contains only a link to an @type: Dataset node
                if (count($mention) === 1) {
                    // resolve the id to get the full node content
                    $fullMention = $this->getNodeFromId($mention['@id']);
                }
                if ($this->switchToEscapeOutput) {
                    $fullMention['name'] = Tools::dontFilterInputEscapeOutput($fullMention['name']);
                }
                $linkHtml .= sprintf("<li><a href='%s'>%s</a></li>", $fullMention['@id'], $fullMention['name']);
            }
            $linkHtml .= '</ul>';
            $this->Entity->patch(Action::Update, array('bodyappend' => $linkHtml));
        }

        // COMMENTS
        if (isset($dataset['comment'])) {
            foreach ($dataset['comment'] as $comment) {
                // for backward compatibility with elabftw's .eln from before 4.9, the "comment" attribute MAY contain all, instead of just being a link with an @id
                $fullComment = $comment;
                // after 4.9 the "comment" attribute contains only a link to an @type: Comment node
                if (count($comment) === 1) {
                    // resolve the id to get the full node content
                    $fullComment = $this->getNodeFromId($comment['@id']);
                }
                $author = $this->getNodeFromId($fullComment['author']['@id']);
                if ($this->switchToEscapeOutput) {
                    $author['givenName'] = Tools::dontFilterInputEscapeOutput($author['givenName']);
                    $author['familyName'] = Tools::dontFilterInputEscapeOutput($author['familyName']);
                    $fullComment['text'] = Tools::dontFilterInputEscapeOutput($fullComment['text'], true);
                }
                $content = sprintf(
                    "Imported comment from %s %s (%s)\n\n%s",
                    $author['givenName'] ?? '',
                    $author['familyName'] ?? $author['name'] ?? 'Unknown',
                    $fullComment['dateCreated'],
                    $fullComment['text'],
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
        if ($this->switchToEscapeOutput) {
            $file['description'] = Tools::dontFilterInputEscapeOutput($file['description'], true);
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
                // adjust the date - templates won't have a date
                if ($json['date']) {
                    $this->Entity->patch(Action::Update, array('date' => $json['date']));
                }
                if ($this->Entity instanceof Experiments) {
                    // try and adjust the status for experiments
                    $sourceStatus = $json['category'];
                    // let's see if we can find a status like this in target instance
                    $targetStatusArr = (new ExperimentsStatus(new Teams($this->Users, $this->Users->userData['team'])))->readAll();
                    $filteredStatus = array_filter($targetStatusArr, function ($status) use ($sourceStatus) {
                        if ($this->switchToEscapeOutput) {
                            $sourceStatus = Tools::dontFilterInputEscapeOutput($sourceStatus);
                        }
                        return $status['title'] === $sourceStatus;
                    });
                    if (!empty($filteredStatus)) {
                        // use array_key_first because the filter will not reset the key numbering
                        $this->Entity->patch(Action::Update, array('category' => (string) $filteredStatus[array_key_first($filteredStatus)]['id']));
                    }
                }
            }
            if ($json['metadata'] !== null) {
                // ToDo: does $json['metadata'] need escape switch?
                $metadata = json_encode($json['metadata'], JSON_THROW_ON_ERROR, 512);
                if ($this->switchToEscapeOutput) {
                    $metadata = Tools::dontFilterInputEscapeOutputMetadata($metadata);
                }
                $this->Entity->patch(Action::Update, array('metadata' => $metadata));
            }
            // add steps
            if (!empty($json['steps'])) {
                foreach ($json['steps'] as $step) {
                    if ($this->switchToEscapeOutput) {
                        $step['body'] = Tools::dontFilterInputEscapeOutput($step['body']);
                    }
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
            if ($this->switchToEscapeOutput) {
                $subpart['description'] = Tools::dontFilterInputEscapeOutput($subpart['description'], true);
            }
            $html .= '<li>' . basename($subpart['@id']) . ' ' . ($subpart['description'] ?? '') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
