<?php
/**
 * \Elabftw\Elabftw\ImportCsv
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Import items from a csv file.
 */
class ImportCsv extends AbstractImport
{
    /** @var int $inserted number of items we got into the database */
    public $inserted = 0;

    /** @var resource|false $handle our file handle */
    private $handle;

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

        $this->openFile();
        $this->readCsv();
    }

    /**
     * Do the work
     *
     * @return void
     */
    private function readCsv(): void
    {
        $row = 0;
        $column = array();
        // loop the lines
        while ($data = fgetcsv($this->handle, 0, ",")) {
            $num = count($data);
            // get the column names (first line)
            if ($row == 0) {
                for ($i = 0; $i < $num; $i++) {
                    $column[] = $data[$i];
                }
                $row++;
                continue;
            }
            $row++;

            $title = $data[2];
            if (empty($title)) {
                $title = _('Untitled');
            }
            $body = '';
            $j = 0;
            foreach ($data as $line) {
                $body .= "<p><strong>" . $column[$j] . " :</strong> " . $line . '</p>';
                $j++;
            }
            // clean the body
            $body = str_replace('<p><strong> :</strong> </p>', '', $body);

            // SQL for importing
            $sql = "INSERT INTO items(team, title, date, body, userid, type)
                VALUES(:team, :title, :date, :body, :userid, :type)";
            $req = $this->Db->prepare($sql);
            $result = $req->execute(array(
                'team' => $this->Users->userData['team'],
                'title' => $title,
                'date' => Tools::kdate(),
                'body' => $body,
                'userid' => $this->Users->userid,
                'type' => $this->target
            ));
            if (!$result) {
                throw new Exception('Error in SQL query!');
            }
            $this->inserted++;
        }
    }

    /**
     * Open the file, as the name suggests
     *
     * @throws RuntimeException
     * @return void
     */
    protected function openFile(): void
    {
        $this->handle = fopen($this->UploadedFile->getPathname(), 'rb');
        if ($this->handle === false) {
            throw new RuntimeException('Cannot open file!');
        }
    }

    /**
     * Close our open file
     *
     */
    public function __destruct()
    {
        fclose($this->handle);
    }
}
