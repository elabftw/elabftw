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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
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

    /** @var string $delimiter the separation character of the csv provided by user */
    private $delimiter;

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
        $this->delimiter = $request->request->filter('delimiter', null, FILTER_SANITIZE_STRING);
        if ($this->delimiter === 'tab') {
            $this->delimiter = "\t";
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
        $csv->setDelimiter($this->delimiter);
        $csv->setHeaderOffset(0);
        $rows = $csv->getRecords();

        // SQL for importing
        $sql = 'INSERT INTO items(team, title, date, body, userid, category, canread)
            VALUES(:team, :title, :date, :body, :userid, :category, :canread)';
        $req = $this->Db->prepare($sql);

        $date = Filter::kdate();

        // now loop the rows and do the import
        foreach ($rows as $row) {
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
            $req->bindParam(':canread', $this->canread);
            if ($req->execute() === false) {
                throw new DatabaseErrorException('Error inserting data in database!');
            }
            $this->inserted++;
        }
    }

    /**
     * Generate a body from a row. Add column name in bold and content after that.
     *
     * @param array<string, string> $row row from the csv
     * @return string
     */
    private function getBodyFromRow(array $row): string
    {
        // get rid of the title
        unset($row['title']);
        // deal with the rest of the columns
        $body = '';
        foreach ($row as $subheader => $content) {
            // translate urls into links
            if (filter_var($content, FILTER_VALIDATE_URL)) {
                $content = '<a href="' . $content . '">' . $content . '</a>';
            }
            $body .= '<p><strong>' . (string) $subheader . ':</strong> ' . $content . '</p>';
        }

        return $body;
    }

    /**
     * Make sure the delimiter character is what is intended
     *
     * @param Reader $csv
     * @return void
     */
    private function checkDelimiter(Reader $csv): void
    {
        $delimitersCount = delimiter_detect($csv, array(',', '|', "\t", ';'), -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // get the first element
        $delimiter = (string) key($delimitersCount);
        if ($delimiter !== $this->delimiter) {
            throw new ImproperActionException(sprintf('It looks like the delimiter is different from «%1$s». Make sure to use «%1$s» as delimiter!', $this->delimiter));
        }
    }
}
