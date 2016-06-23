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
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Context;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

class PropertyReflector extends BaseReflector
{
    /** @var Property */
    protected $property;

    /** @var PropertyProperty */
    protected $node;

    public function __construct(
        Property $property,
        Context $context,
        PropertyProperty $node
    ) {
        parent::__construct($node, $context);
        $this->property = $property;
    }

    public function getName()
    {
        return '$'.parent::getName();
    }

    /**
     * Returns the default value or null if none found.
     *
     * Please note that if the default value is null that this method returns
     * string 'null'.
     *
     * @return null|string
     */
    public function getDefault()
    {
        $result = null;
        if ($this->node->default) {
            $result = $this->getRepresentationOfValue($this->node->default);
        }

        return $result;
    }

    /**
     * Returns the visibility for this item.
     *
     * The returned value should match either of the following:
     *
     * * public
     * * protected
     * * private
     *
     * If a method has no visibility set in the class definition this method
     * will return 'public'.
     *
     * @return string
     */
    public function getVisibility()
    {
        if ($this->property->type & \PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED) {
            return 'protected';
        }
        if ($this->property->type & \PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE) {
            return 'private';
        }

        return 'public';
    }

    /**
     * Returns whether this property is static.
     *
     * @return bool
     */
    public function isStatic()
    {
        return (bool) ($this->property->type & \PhpParser\Node\Stmt\Class_::MODIFIER_STATIC);
    }

    /**
     * Returns the parsed DocBlock.
     *
     * @return DocBlock|null
     */
    public function getDocBlock()
    {
        return $this->extractDocBlock($this->property);
    }
}
