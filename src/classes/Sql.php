<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\FilesystemErrorException;
use function file;

/**
 * For SQL operations from files
 */
class Sql
{
    /** @var Db $Db SQL Database */
    private $Db;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Read a SQL file from src/sql folder and execute the contents
     *
     * @param string $filename
     * @return void
     */
    public function execFile(string $filename): void
    {
        $path = dirname(__DIR__) . '/sql/' . $filename;
        // temporary variable, used to store current query
        $queryline = '';
        // read in entire file as array
        $lines = file($path);
        if ($lines === false) {
            throw new FilesystemErrorException('Error reading file: ' . $path);
        }
        // loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment or blank line
            if ($line === "\n" || $line === '' || strpos($line, '--') === 0) {
                continue;
            }

            // Add this line to the current segment
            $queryline .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (trim($line)[\mb_strlen(trim($line)) - 1] === ';') {
                // Perform the query
                $this->Db->q($queryline);
                // Reset temp variable to empty
                $queryline = '';
            }
        }
    }
}
