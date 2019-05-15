<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Tools;
use Elabftw\Models\Users;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use function League\Csv\delimiter_detect;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Import items from a csv file.
 */
class ImportCsv extends AbstractImport
{
    /** @var int $inserted number of items we got into the database */
    public $inserted = 0;

    /**
     * Constructor
     *
     * @param Users $users instance of Users
     * @param Request $request instance of Request
     * @return void
     */
    public function __construct(Users $users, Request $request)
    {
        parent::__construct($users, $request);
    }

    /**
     * Generate a body from a row. Add column name in bold and content after that.
     *
     * @param array $row row from the csv
     * @return string
     */
    private function getBodyFromRow(array $row): string
    {
        // get rid of the title
        unset($row['title']);
        // deal with the rest of the columns
        $body = '';
        foreach ($row as $subheader => $content) {
            $body .= "<p><strong>" . $subheader . ":</strong> " . $content . '</p>';
        }

        return $body;
    }

    /**
     * Make sure the delimiter character is ','
     *
     * @param Reader $csv
     * @return void
     */
    private function checkDelimiter(Reader $csv): void
    {
        $delimitersCount = delimiter_detect($csv, [",", "|", "\t", ";"], -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // get the first element
        $delimiter = key($delimitersCount);
        if ($delimiter !== ',') {
            throw new ImproperActionException("It looks like the delimiter is different from «,». Make sure to use «,» as delimiter!");
        }
    }

    /**
     * Do the work
     *
     * @throws ImproperActionException
     * @return void
     */
    public function import(): void
    {
        $csv = Reader::createFromPath($this->UploadedFile->getPathname(), 'r');
        $this->checkDelimiter($csv);
        $csv->setHeaderOffset(0);

        // SQL for importing
        $sql = "INSERT INTO items(team, title, date, body, userid, category, visibility)
            VALUES(:team, :title, :date, :body, :userid, :category, :visibility)";
        $req = $this->Db->prepare($sql);

        $date = Tools::kdate();

        // now loop the rows and do the import
        foreach ($csv as $row) {
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $body = $this->getBodyFromRow($row);

            $req->bindParam(':team', $this->Users->userData['team']);
            $req->bindParam(':title', $row['title']);
            $req->bindParam(':date', $date);
            $req->bindParam(':body', $body);
            $req->bindParam(':userid', $this->Users->userData['userid']);
            $req->bindParam(':category', $this->target);
            $req->bindParam(':visibility', $this->visibility);
            if ($req->execute() === false) {
                throw new DatabaseErrorException('Error inserting data in database!');
            }
            $this->inserted++;
        }
    }
}
