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

namespace phpDocumentor\Reflection\ClassReflector;

use phpDocumentor\Reflection\BaseReflector;
use phpDocumentor\Reflection\ConstantReflector as BaseConstantReflector;
use phpDocumentor\Reflection\DocBlock\Context;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Const_;

class ConstantReflector extends BaseConstantReflector
{
    /** @var ClassConst */
    protected $constant;

    /**
     * Registers the Constant Statement and Node with this reflector.
     *
     * @param ClassConst $stmt
     * @param Context $context
     * @param Const_ $node
     */
    public function __construct(
        ClassConst $stmt,
        Context $context,
        Const_ $node
    ) {
        BaseReflector::__construct($node, $context);
        $this->constant = $stmt;
    }
}
