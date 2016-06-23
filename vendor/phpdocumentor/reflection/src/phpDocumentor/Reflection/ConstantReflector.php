<?php
/**
 * phpDocumentor
 *
 * PHP Version 5.3
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2012 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Context;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\Const_ as ConstStmt;

/**
 * Provides Static Reflection for file-level constants.
 *
 * @author  Mike van Riel <mike.vanriel@naenius.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://phpdoc.org
 */
class ConstantReflector extends BaseReflector
{
    /** @var ConstStmt */
    protected $constant;

    /** @var Const_ */
    protected $node;

    /**
     * Registers the Constant Statement and Node with this reflector.
     *
     * @param ConstStmt $stmt
     * @param Const_ $node
     */
    public function __construct(
        ConstStmt $stmt,
        Context $context,
        Const_ $node
    ) {
        parent::__construct($node, $context);
        $this->constant = $stmt;
    }

    /**
     * Returns the value contained in this Constant.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->getRepresentationOfValue($this->node->value);
    }

    /**
     * Returns the parsed DocBlock.
     *
     * @return DocBlock|null
     */
    public function getDocBlock()
    {
        return $this->extractDocBlock($this->constant);
    }
}
