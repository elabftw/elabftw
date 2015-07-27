<?php
/**
 * File containing the ezcDocumentBBCodeToken struct
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
 */

/**
 * Struct for BBCode document document tokens
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentBBCodeToken extends ezcBaseStruct
{
    /**
     * Token content
     *
     * @var mixed
     */
    public $content;

    /**
     * Line of the token in the source file
     *
     * @var int
     */
    public $line;

    /**
     * Position of the token in its line.
     *
     * @var int
     */
    public $position;

    /**
     * Construct BBCode token
     *
     * @ignore
     * @param string $content
     * @param int $line
     * @param int $position
     * @return void
     */
    public function __construct( $content, $line, $position = 0 )
    {
        $this->content  = $content;
        $this->line     = $line;
        $this->position = $position;
    }

    /**
     * Set state after var_export
     *
     * @param array $properties
     * @return void
     * @ignore
     */
    public static function __set_state( $properties )
    {
        return null;
    }
}

?>
