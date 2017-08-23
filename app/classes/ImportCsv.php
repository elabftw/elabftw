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
namespace Elabftw\Elabftw;

use Exception;

/**
 * Import items from a csv file.
 */
class ImportCsv extends AbstractImport
{
    /** @var Users $Users the current user */
    private $Users;

    /** @var Db $Db SQL Database */
    private $Db;

    /** @var int $itemType the category in which we do the import */
    private $itemType;

    /** @var int $inserted number of items we got into the database */
    public $inserted = 0;

    /** @var resource $handle our file handle */
    private $handle;

    /**
     * Assign item type
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;

        $this->checkFileReadable();
        $this->checkMimeType();
        $this->itemType = $this->getTarget();
        $this->openFile();
        $this->readCsv();
    }

    /**
     * Open the file, as the name suggests
     *
     * @throws Exception
     */
    protected function openFile()
    {
        $this->handle = fopen($this->getFilePath(), 'r');
        if ($this->handle === false) {
            throw new Exception('Cannot open file!');
        }
    }

    /**
     * Do the work
     *
     */
    private function readCsv()
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
                'type' => $this->itemType
            ));
            if (!$result) {
                throw new Exception('Error in SQL query!');
            }
            $this->inserted++;
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
