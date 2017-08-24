<?php
/**
 * \Elabftw\Elabftw\AbstractImport
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
 * Mother class of ImportCsv and ImportZip
 */
abstract class AbstractImport
{
    /**
     * Read the file input
     *
     * @return void
     */
    abstract protected function openFile();

    /**
     * Get the temporary uploaded file
     *
     * @return string a sha512 hash of uniqid()
     */
    protected function getFilePath()
    {
        return $_FILES['file']['tmp_name'];
    }

    /**
     * Get what type we want
     *
     * @return int The type of item
     */
    protected function getTarget()
    {
        if (isset($_COOKIE['itemType']) && Tools::checkId($_COOKIE['itemType'])) {
            return $_COOKIE['itemType'];
        }
        throw new Exception('No cookies found. Import aborted.');
    }

    /**
     * Try to read the file we have
     *
     * @throws Exception if cannot read the file
     * @return bool
     */
    protected function checkFileReadable()
    {
        if (is_readable($_FILES['file']['tmp_name'])) {
            return true;
        }
        throw new Exception(_("Could not open the file."));
    }

    /**
     * Look at mime type. not a trusted source, but it can prevent dumb errors
     * There is null in the mimes array because it can happen that elabftw files are like that.
     *
     * @throws Exception if the mime type is not whitelisted
     * @return bool
     */
    protected function checkMimeType()
    {
        $mimes = array(null, 'application/vnd.ms-excel', 'text/plain',
            'text/csv', 'text/tsv',
            'application/zip', 'application/force-download', 'application/x-zip-compressed');

        if (in_array($_FILES['file']['type'], $mimes)) {
            return true;
        }
        throw new Exception(_("This doesn't look like the right kind of file. Import aborted."));
    }
}
