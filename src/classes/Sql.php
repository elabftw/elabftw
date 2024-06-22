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

use Elabftw\Exceptions\DatabaseErrorException;
use League\Flysystem\FilesystemOperator;
use PDOException;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function explode;
use function str_ends_with;
use function str_repeat;
use function strlen;
use function strtoupper;
use function trim;

/**
 * For SQL operations from files
 */
class Sql
{
    private Db $Db;

    public function __construct(private FilesystemOperator $filesystem, private ?OutputInterface $output = null)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Read a SQL file from a folder and execute the contents
     */
    public function execFile(string $filename, bool $force = false): int
    {
        if ($this->output !== null) {
            // add two for the spaces around
            $len = strlen($filename) + 2;
            $this->output->writeln(sprintf('<bg=green;fg=black>%s</>', str_repeat(' ', $len)));
            $this->output->writeln(sprintf('<bg=green;fg=black> %s </>', strtoupper($filename)));
            $this->output->writeln(sprintf('<bg=green;fg=black>%s</>', str_repeat(' ', $len)));
        }
        $lines = $this->getLines($filename);
        // temporary variable, used to store current query
        $queryline = '';
        $lineCount = 0;
        // loop through each line
        foreach ($lines as $line) {
            // Add this line to the current segment
            $queryline .= ' ' . trim($line);
            // If it has a semicolon at the end, it's the end of the query
            if (str_ends_with($line, ';')) {
                // display which query we are running
                if ($this->output !== null) {
                    $this->output->writeln('Executing: ' . $queryline);
                }
                // Perform the query
                try {
                    $this->Db->q($queryline);
                } catch (PDOException | DatabaseErrorException $e) {
                    if ($force) {
                        if ($this->output !== null) {
                            $this->output->writeln('<bg=yellow;fg=black>WARNING: Ignoring error because of force option active.</>');
                            $this->output->writeln($e->getMessage());
                        }
                        // Reset temp variable to empty
                        $queryline = '';
                        $lineCount++;
                        continue;
                    }
                    throw new DatabaseErrorException($e);
                }
                // Reset temp variable to empty
                $queryline = '';
                $lineCount++;
            }
        }
        return $lineCount;
    }

    /**
     * Read a file and return the significant lines as array
     */
    private function getLines(string $filename): array
    {
        $content = $this->filesystem->read($filename);
        $linesArr = explode(PHP_EOL, $content);
        // now filter out the uninteresting lines
        return array_filter(
            $linesArr,
            fn(string $v): bool => !empty($v) && !preg_match('/^\s*(?:--|#|\/\*(?!!).*\*\/)/', $v),
        );
    }
}
