<?php
/**
 * \Elabftw\Elabftw\Make
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;

/**
 * Mother class of MakeCsv, MakePdf and MakeZip
 */
abstract class Make
{
    /** the name of the file on disk */
    public $fileName;
    /** the relative path of this file */
    public $filePath;

    /** child classes need to implement that */
    abstract protected function getCleanName();

    /**
     * Generate the long file name and path
     *
     */
    protected function generateFileName()
    {
        $this->fileName = hash("sha512", uniqid(rand(), true));
        $this->filePath = ELAB_ROOT . 'uploads/tmp/' . $this->fileName;
    }

    /**
     * Validate the type we have.
     *
     * @param $type The type (experiments or items)
     * @return string
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
