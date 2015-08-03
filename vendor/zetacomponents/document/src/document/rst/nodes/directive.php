<?php
/**
 * File containing the ezcDocumentRstDirectiveNode struct
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
 * The AST node for RST document directives
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentRstDirectiveNode extends ezcDocumentRstBlockNode
{
    /**
     * Directive target identifier
     *
     * @var string
     */
    public $identifier;

    /**
     * Directive paramters
     *
     * @var string
     */
    public $parameters;

    /**
     * Directive content tokens
     *
     * @var array
     */
    public $tokens;

    /**
     * Directive options
     *
     * @var array
     */
    public $options;

    /**
     * Construct RST document node
     *
     * @param ezcDocumentRstToken $token
     * @param string $identifier
     * @return void
     */
    public function __construct( ezcDocumentRstToken $token, $identifier )
    {
        // Perhaps check, that only node of type section and metadata are
        // added.
        parent::__construct( $token, self::DIRECTIVE );
        $this->identifier = $identifier;
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
        $node = new ezcDocumentRstDirectiveNode(
            $properties['token'],
            $properties['identifier']
        );

        $node->nodes       = $properties['nodes'];
        $node->parameters  = $properties['parameters'];
        $node->options     = $properties['options'];
        $node->indentation = isset( $properties['indentation'] ) ? $properties['indentation'] : 0;

        if ( isset( $properties['tokens'] ) )
        {
            $node->tokens = $properties['tokens'];
        }

        return $node;
    }
}

?>
