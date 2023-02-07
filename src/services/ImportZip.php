<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function basename;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use function is_readable;
use function json_decode;
use League\Flysystem\UnableToReadFile;
use function mb_strlen;
use PDO;

/**
 * Import a .elabftw.zip file into the database.
 */
class ImportZip extends AbstractImportZip
{
    /**
     * Do the import
     * We get all the info we need from the embedded .json file
     */
    public function import(): void
    {
        $file = '/.elabftw.json';
        try {
            $content = $this->fs->read($this->tmpDir . $file);
        } catch (UnableToReadFile) {
            throw new ImproperActionException(sprintf(_('Error: could not read archive file properly! (missing %s)'), $file));
        }
        $this->importAll(json_decode($content, true, 512, JSON_THROW_ON_ERROR));
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

        if ($this->Entity instanceof Experiments) {
            $sql = 'INSERT into experiments(title, date, body, userid, canread, canwrite, category, elabid, metadata)
                VALUES(:title, :date, :body, :userid, :canread, :canwrite, :category, :elabid, :metadata)';
        }

        // make sure there is an elabid (might not exist for items before v4.0)
        $elabid = $item['elabid'] ?? Tools::generateElabid();

        $req = $this->Db->prepare($sql);
        if ($this->Entity instanceof Items) {
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        }
        $req->bindParam(':title', $item['title']);
        $req->bindParam(':date', $item['date']);
        $req->bindParam(':body', $item['body']);
        $req->bindValue(':canread', $this->canread);
        $req->bindValue(':canwrite', $this->canwrite);
        $req->bindParam(':elabid', $elabid);
        $req->bindParam(':metadata', $item['metadata']);
        if ($this->Entity instanceof Items) {
            $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':category', $this->targetNumber, PDO::PARAM_INT);
        } else {
            $req->bindParam(':userid', $this->targetNumber, PDO::PARAM_INT);
            $req->bindValue(':category', $this->getDefaultStatus());
        }

        $this->Db->execute($req);

        $newItemId = $this->Db->lastInsertId();

        // create necessary objects
        $this->Entity->setId($newItemId);

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
            $this->Entity->patch(Action::Update, array('title' => $item['title'], 'date' => $item['date'], 'bodyappend' => $header . $linkText . $end));
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
        $tagsArr = explode(self::TAGS_SEPARATOR, $tags);
        foreach ($tagsArr as $tag) {
            $this->Entity->Tags->postAction(Action::Create, array('tag' => $tag));
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
                    if ($this->Entity instanceof Experiments) {
                        $filePath = $this->tmpPath . '/' .
                            $item['date'] . ' - ' . $titlePath . ' - ' . $shortElabid . '/' . $file['real_name'];
                    } else {
                        $filePath = $this->tmpPath . '/' .
                            $item['category'] . ' - ' . $titlePath . ' - ' . $shortElabid . '/' . $file['real_name'];
                    }

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
