<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use DateTimeImmutable;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\Action;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractTemplateEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Uploads;
use League\Flysystem\UnableToReadFile;

use function basename;
use function hash_file;
use function json_decode;
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

        $root_node_has_part = array();
        foreach ($this->graph as $node) {
            // find the node describing the crate
            if ($node['@id'] === './') {
                $root_node_has_part = $node['hasPart'];
            }
            // detect old elabftw (<5.0.0-beta2) versions where we need to decode characters
            // only newer versions have the areaServed attribute
            if ($node['@id'] === 'ro-crate-metadata.json' &&
                array_key_exists('sdPublisher', $node) &&
                $node['sdPublisher']['name'] === 'eLabFTW' &&
                !array_key_exists('areaServed', $node['sdPublisher'])) {
                $this->switchToEscapeOutput = true;
            }
        }

        // loop over each hasPart of the root node
        foreach ($root_node_has_part as $part) {
            $this->importRootDataset($this->getNodeFromId($part['@id']));
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

    /**
     * This is the main Dataset `@type` node.
     */
    private function importRootDataset(array $dataset): void
    {
        $createTarget = $this->targetNumber;
        $title = $this->transformIfNecessary($dataset['name'] ?? _('Untitled'));

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
        // here we use "text" or "description" attribute as main text
        $this->Entity->patch(Action::Update, array('title' => $title, 'bodyappend' => ($dataset['text'] ?? '') . ($dataset['description'] ?? '')));

        // TAGS: should normally be a comma separated string, but we allow array for BC
        if (!empty($dataset['keywords'])) {
            $tags = $dataset['keywords'];
            if (is_string($tags)) {
                $tags = explode(',', $tags);
            }
            foreach ($tags as $tag) {
                if (!empty($tag)) {
                    $this->Entity->Tags->postAction(
                        Action::Create,
                        array('tag' => $this->transformIfNecessary($tag)),
                    );
                }
            }
        }

        // LINKS
        if (!empty($dataset['mentions'])) {
            $linkHtml = sprintf('<h1>%s</h1><ul>', _('Links'));
            foreach($dataset['mentions'] as $mention) {
                // for backward compatibility with elabftw's .eln from before 4.9, the "mention" attribute MAY contain all, instead of just being a link with an @id
                // after 4.9 the "mention" attribute contains only a link to an @type: Dataset node
                if (count($mention) === 1) {
                    // resolve the id to get the full node content
                    $mention = $this->getNodeFromId($mention['@id']);
                }
                $linkHtml .= sprintf(
                    "<li><a href='%s'>%s</a></li>",
                    $mention['@id'],
                    $this->transformIfNecessary($mention['name']),
                );
            }
            $linkHtml .= '</ul>';
            $this->Entity->patch(Action::Update, array('bodyappend' => $linkHtml));
        }

        // let's see if we can find a category like this in target instance
        $Teams = new Teams($this->Users, $this->Users->userData['team']);
        // yes, this opens it up to normal users that normally cannot create status and category, but user experience takes over this consideration here
        $Teams->bypassWritePermission = true;

        // CATEGORY
        if (isset($dataset['category'])) {
            // let's see if we can find a category like this in target instance
            if ($this->Entity instanceof Experiments) {
                $Category = new ExperimentsCategories($Teams);
            } else { // items
                $Category = new ItemsTypes($this->Users, $this->Users->userData['team']);
                // yes, this opens it up to normal users that normally cannot create status and category, but user experience takes over this consideration here
                $Category->bypassWritePermission = true;
            }
            $categoryId = $Category->getIdempotentIdFromTitle($dataset['category']);
            $this->Entity->patch(Action::Update, array('category' => (string) $categoryId));
        }

        // STATUS
        if (isset($dataset['status'])) {
            if ($this->Entity instanceof Experiments) {
                $Status = new ExperimentsStatus($Teams);
            } else { // items
                $Status = new ItemsStatus($Teams);
            }
            $statusId = $Status->getIdempotentIdFromTitle($dataset['status']);
            $this->Entity->patch(Action::Update, array('status' => (string) $statusId));
        }

        // COMMENTS
        if (!empty($dataset['comment'])) {
            foreach ($dataset['comment'] as $comment) {
                // for backward compatibility with elabftw's .eln from before 4.9, the "comment" attribute MAY contain all, instead of just being a link with an @id
                // after 4.9 the "comment" attribute contains only a link to an @type: Comment node
                if (count($comment) === 1) {
                    // resolve the id to get the full node content
                    $comment = $this->getNodeFromId($comment['@id']);
                }
                $author = $this->getNodeFromId($comment['author']['@id']);
                $content = sprintf(
                    "Imported comment from %s %s (%s)\n\n%s",
                    $this->transformIfNecessary($author['givenName'] ?? ''),
                    $this->transformIfNecessary($author['familyName'] ?? '') ?: $author['name'] ?? 'Unknown',
                    $comment['dateCreated'],
                    $this->transformIfNecessary($comment['text'] ?? '', true),
                );
                $this->Entity->Comments->postAction(Action::Create, array('comment' => $content));
            }
        }

        // now we import all the remaining attributes as text/links in the main text
        // we still have an allowlist of attributes imported, which also allows to switch between the kind of values expected
        $html = '';
        foreach ($dataset as $attributeName => $value) {
            switch($attributeName) {
                case 'author':
                    $html .= $this->authorToHtml($value);
                    break;
                case 'funder':
                    $html .= $this->attrToHtml($value, _(ucfirst($attributeName)));
                    break;
                case 'citation':
                case 'license':
                    $html .= sprintf('<h1>%s</h1><ul><li><a href="%s">%s</a></li></ul>', _(ucfirst($attributeName)), $value['@id'], $value['@id']);
                    break;
                default:
            }
        }
        $this->Entity->patch(Action::Update, array('bodyappend' => $html));

        // also save the Dataset node as a .json file so we don't lose information with things not imported
        $this->Entity->Uploads->postAction(Action::CreateFromString, array(
            'file_type' => FileFromString::Json->value,
            'real_name' => 'dataset-node-from-ro-crate.json',
            'content' => json_encode($dataset, JSON_THROW_ON_ERROR, 1024),
        ));

        $this->inserted++;
        // now loop over the parts of this node to find the rest of the files
        // the getNodeFromId might return nothing but that's okay, we just continue to try and find stuff
        foreach ($dataset['hasPart'] as $part) {
            $this->importPart($this->getNodeFromId($part['@id']));
        }
    }

    private function authorToHtml(array $node): string
    {
        $html = sprintf('<h1>%s</h1><ul>', _('Author'));
        $fullNode = $this->getNodeFromId($node['@id']);
        $html .= sprintf(
            '<li>%s %s %s</li>',
            $this->transformIfNecessary($fullNode['givenName'] ?? ''),
            $this->transformIfNecessary($fullNode['familyName'] ?? ''),
            $this->transformIfNecessary($fullNode['identifier'] ?? ''),
        );
        return $html . '</ul>';
    }

    private function attrToHtml(array $attr, string $title): string
    {
        $html = sprintf('<h1>%s</h1><ul>', $title);
        foreach ($attr as $elem) {
            if (is_string($elem)) {
                $html .= sprintf('<li><a href="%s">%s</a></li>', $elem, $elem);
                continue;
            }
            $node = $this->getNodeFromId($elem['@id']);
            $html .= sprintf('<li><a href="%s">%s</a></li>', $node['@id'], $node['name']);
        }
        return $html . '</ul>';
    }

    private function importPart(array $part): void
    {
        if (empty($part['@type'])) {
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
        if (empty($file['sha256']) || hash_file('sha256', $filepath) !== $file['sha256']) {
            throw new ImproperActionException(sprintf('Error during import: %s has incorrect sha256 sum.', basename($filepath)));
        }
        $newUploadId = $this->Entity->Uploads->create(new CreateUpload(
            $file['name'] ?? basename($file['@id']),
            $filepath,
            $this->transformIfNecessary($file['description'] ?? '', true) ?: null,
        ));
        // the alternateName holds the previous long_name of the file
        if (!empty($file['alternateName'])) {
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
                // RATING
                $this->Entity->patch(Action::Update, array('rating' => $json['rating'] ?? ''));
                // ADJUST THE DATE - TEMPLATES WON'T HAVE A DATE
                if ($json['date']) {
                    $this->Entity->patch(Action::Update, array('date' => $json['date']));
                }
            }
            if (!empty($json['metadata_decoded'])) {
                $metadataStr = json_encode($json['metadata_decoded'], JSON_THROW_ON_ERROR, 512);
                $cleanMetadata = $this->transformIfNecessary($metadataStr, isMetadata: true);
                $this->Entity->patch(
                    Action::Update,
                    array('metadata' => $cleanMetadata),
                );
            }
            // add steps
            if (!empty($json['steps'])) {
                foreach ($json['steps'] as $step) {
                    if (!empty($step['body'])) {
                        $step['body'] = $this->transformIfNecessary($step['body']);
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
            $html .= sprintf(
                '<li>%s %s</li>',
                basename($subpart['@id']),
                $this->transformIfNecessary($subpart['description'] ?? ''),
            );
        }
        $html .= '</ul>';
        return $html;
    }
}
