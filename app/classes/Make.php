<?php
/**
 * \Elabftw\Elabftw\Make
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
 * Mother class of MakeCsv, MakePdf and MakeZip
 */
abstract class Make
{
    /** pdo object */
    protected $pdo;
    /** type can be experiments or items */
    protected $type;

    /** child classes need to implement that
     *
     * @return string
     */
    abstract protected function getCleanName();

    /**
     * Generate a long and unique filename
     *
     * @return string a sha512 hash of uniqid()
     */
    protected function getFileName()
    {
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * Attach the absolute path to a filename
     *
     * @param string $fileName
     * @param bool $tmp set to true if you want a temporary path (in uploads/tmp)
     * @return string Absolute path
     */
    protected function getFilePath($fileName, $tmp = false)
    {
        $tempPath = '';

        if ($tmp) {
            $tempPath = 'tmp/';
        }
        return ELAB_ROOT . 'uploads/' . $tempPath . $fileName;
    }
    /**
     * Validate the type we have.
     *
     * @param string $type The type (experiments or items)
     * @return string The valid type
     */
    protected function checkType($type)
    {
        $correctValuesArr = array('experiments', 'items');
        if (!in_array($type, $correctValuesArr)) {
            throw new Exception('Bad type!');
        }
        return $type;
    }
}
