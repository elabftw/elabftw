<?php
/**
 * File containing the ezcDocumentPcssDirective class
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
 * Pdf CSS layout directive.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentPcssDirective extends ezcBaseStruct
{
    /**
     * Directive address
     *
     * @var mixed
     */
    public $address;

    /**
     * Array of formatting rules
     *
     * @var array
     */
    public $formats;

    /**
     * File, directive has been extracted from
     *
     * @var string
     */
    public $file;

    /**
     * Line of directive
     *
     * @var int
     */
    public $line;

    /**
     * Position of directive
     *
     * @var int
     */
    public $position;

    /**
     * Regular expression compiled from directive address
     *
     * @var string
     */
    protected $regularExpression = null;

    /**
     * Construct directive from address and formats
     *
     * @param string $address
     * @param array $formats
     * @param string $file
     * @param int $line
     * @param int $position
     */
    public function __construct( $address, array $formats, $file = null, $line = null, $position = null )
    {
        $this->address  = $address;
        $this->formats  = $formats;
        $this->file     = $file;
        $this->line     = $line;
        $this->position = $position;
    }
}
?>
