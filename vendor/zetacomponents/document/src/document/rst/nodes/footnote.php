<?php
/**
 * File containing the ezcDocumentRstFootnoteNode struct
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
 * The footnote AST node
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentRstFootnoteNode extends ezcDocumentRstNode
{
    /**
     * Footnote target name
     *
     * @var array
     */
    public $name;

    /**
     * Footnote number
     *
     * @var int
     */
    public $number;

    /**
     * Type of footnote. May be either a normal footnote, or a citation
     * reference.
     *
     * @var int
     */
    public $footnoteType = self::NUMBERED;

    /**
     * Numbered footnote
     */
    const NUMBERED = 1;

    /**
     * Auto numbered footnote
     */
    const AUTO_NUMBERED = 2;

    /**
     * Labeled auto numbered footnote
     */
    const LABELED = 4;

    /**
     * Footnote using symbols
     */
    const SYMBOL = 8;

    /**
     * Footnote is citation reference
     */
    const CITATION = 16;

    /**
     * Construct RST document node
     *
     * @param ezcDocumentRstToken $token
     * @param array $name
     * @param int $footnoteType
     * @return void
     */
    public function __construct( ezcDocumentRstToken $token, array $name, $footnoteType = self::NUMBERED )
    {
        // Perhaps check, that only node of type section and metadata are
        // added.
        parent::__construct( $token, self::FOOTNOTE );
        $this->name = $name;

        $this->footnoteType = $footnoteType;
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
        $node = new ezcDocumentRstFootnoteNode(
            $properties['token'],
            $properties['name']
        );

        $node->nodes        = $properties['nodes'];
        $node->footnoteType = $properties['footnoteType'];
        return $node;
    }
}

?>
