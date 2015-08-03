<?php
/**
 * File containing the ezcDocumentRstEnumeratedListNode struct
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
 * The enumeration lsit item AST node
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentRstEnumeratedListNode extends ezcDocumentRstBlockNode
{
    /**
     * Enumerated list type, should be one of the following:
     *  - 1: Numeric
     *  - 2: Uppercase
     *  - 3: Lowercase
     *  - 4: Uppercase roman
     *  - 5: Lowercase roman
     *
     * @var int
     */
    public $listType = 0;

    /**
     * Storage for complete textual representation of the enunmeration list
     * marker, for the case, that enumeration list items needs to be converted
     * back to text.
     *
     * @var string
     */
    public $text;

    /**
     * Construct RST document node
     *
     * @param ezcDocumentRstToken $token
     * @return void
     */
    public function __construct( ezcDocumentRstToken $token )
    {
        // Perhaps check, that only node of type section and metadata are
        // added.
        parent::__construct( $token, self::ENUMERATED_LIST );
    }

    /**
     * Return node content, if available somehow
     *
     * @return string
     */
    protected function content()
    {
        return trim( $this->token->content ) . ', ' . $this->indentation;
    }

    /**
     * Numeric enumeration list type
     */
    const NUMERIC = 1;

    /**
     * Uppercase alphanumeric enumeration list type
     */
    const UPPERCASE = 2;

    /**
     * Lowercase alphanumeric enumeration list type
     */
    const LOWERCASE = 3;

    /**
     * Uppercase roman enumeration list type
     */
    const UPPER_ROMAN = 4;

    /**
     * Lowercase roman enumeration list type
     */
    const LOWER_ROMAN = 5;

    /**
     * Set state after var_export
     *
     * @param array $properties
     * @return void
     * @ignore
     */
    public static function __set_state( $properties )
    {
        $node = new ezcDocumentRstEnumeratedListNode(
            $properties['token']
        );

        $node->type        = $properties['type'];
        $node->nodes       = $properties['nodes'];
        $node->indentation = isset( $properties['indentation'] ) ? $properties['indentation'] : 0;
        $node->text        = isset( $properties['text'] ) ? $properties['text'] : '';
        $node->listType    = isset( $properties['listType'] ) ? $properties['listType'] : false;
        return $node;
    }
}

?>
