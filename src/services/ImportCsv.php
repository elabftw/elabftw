<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\TagParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Traits\EntityTrait;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import items from a csv file.
 */
class ImportCsv extends AbstractImport
{
    use EntityTrait;

    private const TAGS_SEPARATOR = '|';

    // number of items we got into the database
    public int $inserted = 0;

    // the separation character of the csv provided by user
    private string $delimiter;

    public function __construct(Users $users, int $target, string $delimiter, string $canread, UploadedFile $uploadedFile)
    {
        parent::__construct($users, $target, $canread, $uploadedFile);
        $this->delimiter = Filter::sanitize($delimiter);
        if ($this->delimiter === 'tab') {
            $this->delimiter = "\t";
        }
    }

    /**
     * Do the work
     *
     * @throws ImproperActionException
     */
    public function import(): void
    {
        $csv = Reader::createFromPath($this->UploadedFile->getPathname(), 'r');
        $this->checkDelimiter($csv);
        $csv->setDelimiter($this->delimiter);
        $csv->setHeaderOffset(0);
        $rows = $csv->getRecords();

        // SQL for importing
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, canread, elabid)
            VALUES(:team, :title, CURDATE(), :body, :userid, :category, :canread, :elabid)';
        $req = $this->Db->prepare($sql);

        // now loop the rows and do the import
        foreach ($rows as $row) {
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $body = $this->getBodyFromRow($row);
            $elabid = $this->generateElabid();

            $req->bindParam(':team', $this->Users->userData['team']);
            $req->bindParam(':title', $row['title']);
            $req->bindParam(':body', $body);
            $req->bindParam(':userid', $this->Users->userData['userid']);
            $req->bindParam(':category', $this->target);
            $req->bindParam(':canread', $this->canread);
            $req->bindParam(':elabid', $elabid);
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

    /**
     * Make sure the delimiter character is what is intended
     */
    private function checkDelimiter(Reader $csv): void
    {
        $delimitersCount = CsvInfo::getDelimiterStats($csv, array(',', '|', "\t", ';'), -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // get the first element
        $delimiter = (string) key($delimitersCount);
        if ($delimiter !== $this->delimiter) {
            throw new ImproperActionException(sprintf('It looks like the delimiter is different from «%1$s». Make sure to use «%1$s» as delimiter!', $this->delimiter));
        }
    }
}
