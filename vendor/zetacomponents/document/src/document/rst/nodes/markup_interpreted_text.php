<?php
/**
 * File containing the ezcDocumentRstMarkupInterpretedTextNode struct
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
 * The inline interpreted text markup AST node
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentRstMarkupInterpretedTextNode extends ezcDocumentRstMarkupNode
{
    /**
     * Text role
     *
     * @var mixed
     */
    public $role = false;

    /**
     * Construct RST document node
     *
     * @param ezcDocumentRstToken $token
     * @param bool $open
     * @return void
     */
    public function __construct( ezcDocumentRstToken $token, $open )
    {
        parent::__construct( $token, self::MARKUP_INTERPRETED );
        $this->openTag = (bool) $open;
    }

    /**
     * Return node content, if available somehow
     *
     * @return string
     */
    protected function content()
    {
        return (string) $this->role;
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
        $node = new ezcDocumentRstMarkupInterpretedTextNode(
            $properties['token'],
            $properties['openTag']
        );

        if ( isset( $properties['role'] ) )
        {
            $node->role = $properties['role'];
        }

        $node->nodes = $properties['nodes'];
        return $node;
    }
}

?>
