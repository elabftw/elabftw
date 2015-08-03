<?php
/**
 * File containing the abstract ezcDocumentAlnumListItemGenerator base class.
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
 * List item generator
 *
 * Abstract base class for alphanumeric list item generators, which implements 
 * an applyStyle() method and an additional constructor argument, so that all 
 * alphanumeric list item generators extending from this class cann be called 
 * to generate lower- and uppercase variants of their list items.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentAlnumListItemGenerator extends ezcDocumentListItemGenerator
{
    /**
     * Constant forcing uppercase alphanumeric list items
     */
    const UPPER = 1;

    /**
     * Constant forcing lowercase alphanumeric list items
     */
    const LOWER = 2;

    /**
     * Style defining if the alphanumeric list items should be
     * lower or upper case.
     * 
     * @var int
     */
    protected $style;

    /**
     * Constructn for upper/lower output
     * 
     * @param int $style 
     * @return void
     */
    public function __construct( $style = self::LOWER )
    {
        $this->style = $style === self::LOWER ? self::LOWER : self::UPPER;
    }

    /**
     * Apply upper/lower-case style to return value.
     * 
     * @param string $string 
     * @return string
     */
    protected function applyStyle( $string )
    {
        switch ( $this->style )
        {
            case self::LOWER:
                return strtolower( $string );

            case self::UPPER:
                return strtoupper( $string );
        }
    }
}

?>
