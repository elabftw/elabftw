<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @author    Erik Baars <baarserik@hotmail.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */
namespace phpDocumentor\Reflection;

use PhpParser\Node\Stmt;

/**
 * Class for testing PhpParser_Node_Stmt.
 *
 * Extends the PhpParser_Node_Stmt so properties and abstract methods can be mocked,
 * and therefore tested.
 *
 * @author    Erik Baars <baarserik@hotmail.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */
class NodeStmtMock extends \PhpParser\Node\Stmt
{
    public $name = null;

    public function setName($val)
    {
        $this->name = $val;
    }

    public function __toString()
    {
        return 'testNodeMock';
    }
}
