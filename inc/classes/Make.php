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
class Make
{
    /**
     * Validate the type we have.
     *
     * @param $type The type (experiments or items)
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
