<?php
/**
 * File containing the abstract ezcDocumentPcssStyleValue base class.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Document
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */

/**
 * Style directive value representation.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentPcssStyleValue extends ezcBaseStruct
{
    /**
     * Directive value
     *
     * @var mixed
     */
    public $value;

    /**
     * Construct value
     *
     * Optionally pass a parsed representation of the value.
     * 
     * @param mixed $value 
     * @return void
     */
    public function __construct( $value = null )
    {
        $this->value = $value;
    }

    /**
     * Parse value string representation
     *
     * Parse the string representation of the value into a usable
     * representation.
     * 
     * @param string $value 
     * @return ezcDocumentPcssStyleValue
     */
    abstract public function parse( $value );

    /**
     * Get regular expression matching the value
     *
     * Return a regular sub expression, which matches all possible values of
     * this value type. The regular expression should NOT contain any named
     * sub-patterns, since it might be repeatedly embedded in some box parser.
     * 
     * @return string
     */
    abstract public function getRegularExpression();

    /**
     * Convert value to string
     *
     * @return string
     */
    abstract public function __toString();
}
?>
