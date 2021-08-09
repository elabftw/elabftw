<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Elabftw;

use function explode;
use League\Flysystem\FilesystemInterface;
use function mb_strlen;
use PHP_EOL;
use function str_starts_with;

/**
 * For SQL operations from files
 */
class Sql
{
    private Db $Db;

    public function __construct(private FilesystemInterface $filesystem)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Read a SQL file from a folder and execute the contents
     */
    public function execFile(string $filename): bool
    {
        $content = $this->filesystem->read($filename);
        // @phpstan-ignore-next-line
        $lines = explode(PHP_EOL, $content);
        // temporary variable, used to store current query
        $queryline = '';
        // loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment or blank line
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }

            // Add this line to the current segment
            $queryline .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (trim($line)[mb_strlen(trim($line)) - 1] === ';') {
                // Perform the query
                $this->Db->q($queryline);
                // Reset temp variable to empty
                $queryline = '';
            }
        }
        return true;
    }
}
