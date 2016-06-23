<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */
namespace phpDocumentor\Reflection;

use PhpParser\Node\Expr;

/**
 * Class for testing PhpParser_Node_Expr.
 *
 * Extends the PhpParser_Node_Expr so properties and abstract methods can be mocked,
 * and therefore tested.
 *
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */
class NodeExprMock extends Expr
{
}
