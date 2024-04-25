<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader;

/**
 * Import entries from a csv file.
 */
class Csv extends AbstractImport
{
    protected array $allowedMimes = array(
        'application/csv',
        'application/vnd.ms-excel',
        'text/plain',
        'text/csv',
        'text/tsv',
    );

    /**
     * Do the work
     *
     * @throws ImproperActionException
     */
    public function import(): void
    {
        // we directly read from temporary uploaded file location and do not need to use the cache folder as no extraction is necessary for a .csv
        $csv = Reader::createFromPath($this->UploadedFile->getPathname(), 'r');
        // get stats about the most likely delimiter
        $delimitersCount = CsvInfo::getDelimiterStats($csv, array(',', '|', "\t", ';'), -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // set the delimiter from the first value
        $csv->setDelimiter((string) key($delimitersCount));
        $csv->setHeaderOffset(0);
        $rows = $csv->getRecords();

        // SQL for importing
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, status, custom_id, canread, canwrite, canbook, elabid, metadata)
            VALUES(:team, :title, CURDATE(), :body, :userid, :category, :status, :custom_id, :canread, :canwrite, :canbook, :elabid, :metadata)';

        if ($this->Entity instanceof Experiments) {
            $sql = 'INSERT INTO experiments(team, title, date, body, userid, category, status, custom_id, canread, canwrite, elabid, metadata)
                VALUES(:team, :title, CURDATE(), :body, :userid, :category, :status, :custom_id, :canread, :canwrite, :elabid, :metadata)';
        }
        $req = $this->Db->prepare($sql);

        // now loop the rows and do the import
        foreach ($rows as $row) {
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $body = $this->getBodyFromRow($row);
            $status = empty($row['status']) ? null : $row['status'];
            $customId = empty($row['custom_id']) ? null : $row['custom_id'];
            $metadata = null;
            if (isset($row['metadata']) && !empty($row['metadata'])) {
                $metadata = $row['metadata'];
            }

            if ($this->Entity instanceof Items) {
                $req->bindParam(':canbook', $this->canread);
            }
            $req->bindParam(':team', $this->Users->userData['team']);
            $req->bindParam(':title', $row['title']);
            $req->bindParam(':body', $body);
            $req->bindParam(':userid', $this->Users->userData['userid']);
            $req->bindParam(':category', $this->targetNumber);
            $req->bindParam(':status', $status);
            $req->bindParam(':custom_id', $customId);
            $req->bindParam(':canread', $this->canread);
            $req->bindParam(':canwrite', $this->canwrite);
            $req->bindValue(':elabid', Tools::generateElabid());
            $req->bindParam(':metadata', $metadata);
            $this->Db->execute($req);
            $newItemId = $this->Db->lastInsertId();

            $this->Entity->setId($newItemId);

            // insert tags from the tags column
            if (isset($row['tags'])) {
                $this->insertTags($row['tags']);
            }

            $this->inserted++;
        }
    }

    /**
     * Generate a body from a row. Add column name and content after that.
     *
     * @param array<string, null|string> $row row from the csv
     */
    private function getBodyFromRow(array $row): string
    {
        // get rid of the title
        unset($row['title']);
        // and the tags
        unset($row['tags']);
        // and the metadata
        unset($row['metadata']);
        // deal with the rest of the columns
        $body = '';
        foreach ($row as $subheader => $content) {
            $contentEscaped = htmlspecialchars($content ??= '');
            // translate urls into links
            if (filter_var($content, FILTER_VALIDATE_URL)) {
                $contentEscaped = sprintf('<a href="%1$s">%1$s</a>', $contentEscaped);
            }
            $body .= sprintf('<p>%s:%s</p>', htmlspecialchars($subheader), $contentEscaped);
        }

        return $body;
    }

    private function insertTags(string $tags): void
    {
        $tagsArr = explode(self::TAGS_SEPARATOR, $tags);
        foreach ($tagsArr as $tag) {
            // maybe it's empty for this row
            if ($tag) {
                $this->Entity->Tags->postAction(Action::Create, array('tag' => $tag));
            }
        }
    }
}
