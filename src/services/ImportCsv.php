<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\TagParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import entries from a csv file.
 */
class ImportCsv extends AbstractImport
{
    public function __construct(Users $users, string $target, string $canread, string $canwrite, UploadedFile $uploadedFile)
    {
        parent::__construct($users, $target, $canread, $canwrite, $uploadedFile);
    }

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

        $createTarget = (string) $this->targetNumber;
        if ($this->Entity instanceof Experiments) {
            // no template
            $createTarget = '-1';
        }
        // SQL for importing
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, canread, canwrite, elabid)
            VALUES(:team, :title, CURDATE(), :body, :userid, :category, :canread, :canwrite, :elabid)';

        if ($this->Entity instanceof Experiments) {
            $sql = 'INSERT INTO experiments(title, date, body, userid, canread, canwrite, category, elabid)
                VALUES(:title, CURDATE(), :body, :userid, :canread, :canwrite, :category, :elabid)';
        }
        $req = $this->Db->prepare($sql);

        // now loop the rows and do the import
        foreach ($rows as $row) {
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $body = $this->getBodyFromRow($row);

            if ($this->Entity instanceof Items) {
                $req->bindParam(':team', $this->Users->userData['team']);
            }
            $req->bindParam(':title', $row['title']);
            $req->bindParam(':body', $body);
            $req->bindParam(':userid', $this->Users->userData['userid']);
            $req->bindParam(':category', $this->targetNumber);
            $req->bindParam(':canread', $this->canread);
            $req->bindParam(':canwrite', $this->canwrite);
            $req->bindValue(':elabid', Tools::generateElabid());
            $this->Db->execute($req);
            $itemId = $this->Db->lastInsertId();

            // insert tags from the tags column
            if (isset($row['tags'])) {
                $this->insertTags($row['tags'], $itemId);
            }

            $this->inserted++;
        }
    }

    /**
     * Generate a body from a row. Add column name and content after that.
     *
     * @param array<string, string> $row row from the csv
     */
    private function getBodyFromRow(array $row): string
    {
        // get rid of the title
        unset($row['title']);
        // and the tags
        unset($row['tags']);
        // deal with the rest of the columns
        $body = '';
        foreach ($row as $subheader => $content) {
            // translate urls into links
            if (filter_var($content, FILTER_VALIDATE_URL)) {
                $content = '<a href="' . $content . '">' . $content . '</a>';
            }
            $body .= '<p>' . (string) $subheader . ': ' . $content . '</p>';
        }

        return $body;
    }

    private function insertTags(string $tags, int $itemId): void
    {
        $tagsArr = explode(self::TAGS_SEPARATOR, $tags);
        $Entity = new Items($this->Users, $itemId);
        foreach ($tagsArr as $tag) {
            // maybe it's empty for this row
            if ($tag) {
                $Entity->Tags->create(new TagParams($tag));
            }
        }
    }
}
