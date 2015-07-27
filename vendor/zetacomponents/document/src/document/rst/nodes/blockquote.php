<?php
/**
 * File containing the ezcDocumentRstBlockquoteNode struct
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
 * The blockquote AST node
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentRstBlockquoteNode extends ezcDocumentRstBlockNode
{
    /**
     * Blockquote annotation
     *
     * @var ezcDocumentRstBlockquoteAnnotationNode
     */
    public $annotation = null;

    /**
     * Indicator telling whether a blockquote has been finished by either a
     * annotation or an explicit blockquote separation marker.
     *
     * @var bool
     */
    public $closed = false;

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
        parent::__construct( $token, self::BLOCKQUOTE );
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
        $node = new ezcDocumentRstBlockquoteNode(
            $properties['token']
        );

        $node->nodes       = $properties['nodes'];
        $node->annotation  = $properties['annotation'];
        $node->closed      = $properties['closed'];
        $node->indentation = isset( $properties['indentation'] ) ? $properties['indentation'] : 0;
        return $node;
    }
}

?>
