<?php
/**
 * @link      http://github.com/zendframework/zend-json for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Json;

/**
 * Encode a string to a native JavaScript expression.
 *
 * This class simply holds a string with a native JavaScript expression,
 * so objects or arrays to be encoded with Zend\Json\Json can contain native
 * JavaScript expressions.
 *
 * Example:
 *
 * <code>
 * $foo = array(
 *     'integer'  => 9,
 *     'string'   => 'test string',
 *     'function' => Zend\Json\Expr(
 *         'function () { window.alert("javascript function encoded by Zend\Json\Json") }'
 *     ),
 * );
 *
 * echo Zend\Json\Json::encode($foo, false, ['enableJsonExprFinder' => true]);
 * </code>
 *
 * The above returns the following JSON (formatted for readability):
 *
 * <code>
 * {
 *   "integer": 9,
 *   "string": "test string",
 *   "function": function () {window.alert("javascript function encoded by Zend\Json\Json")}
 * }
 * </code>
 */
class Expr
{
    /**
     * Storage for javascript expression.
     *
     * @var string
     */
    protected $expression;

    /**
     * @param string $expression The expression to represent.
     */
    public function __construct($expression)
    {
        $this->expression = (string) $expression;
    }

    /**
     * Cast to string
     *
     * @return string holded javascript expression.
     */
    public function __toString()
    {
        return $this->expression;
    }
}
