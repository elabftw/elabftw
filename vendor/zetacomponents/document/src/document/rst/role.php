<?php
/**
 * File containing the ezcDocumentRstTextRole class
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
 * Visitor for RST text roles
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentRstTextRole
{
    /**
     * Current text role RST AST node.
     *
     * @var ezcDocumentRstTextRoleNode
     */
    protected $node;

    /**
     * Complete RST abstract syntax tree, if this is necessary to render the
     * text role.
     *
     * @var ezcDocumentRstDocumentNode
     */
    protected $ast;

    /**
     * Current document base path, especially relevant for file inclusions.
     *
     * @var string
     */
    protected $path;

    /**
     * The calling visitor.
     *
     * @var ezcDocumentRstVisitor
     */
    protected $visitor;

    /**
     * Construct text role from AST and node
     *
     * @param ezcDocumentRstDocumentNode $ast
     * @param string $path
     * @param ezcDocumentRstMarkupInterpretedTextNode $node
     * @return void
     */
    public function __construct( ezcDocumentRstDocumentNode $ast, $path, ezcDocumentRstMarkupInterpretedTextNode $node )
    {
        $this->ast  = $ast;
        $this->path = $path;
        $this->node = $node;
    }

    /**
     * Set the calling vaisitor
     *
     * Pass the visitor which called the rendering function on the text role
     * for optional reference.
     *
     * @param ezcDocumentRstVisitor $visitor
     * @return void
     */
    public function setSourceVisitor( ezcDocumentRstVisitor $visitor )
    {
        $this->visitor = $visitor;
    }

    /**
     * Append text from interpreted text node to given DOMElement
     *
     * @param DOMElement $root
     * @return void
     */
    protected function appendText( DOMElement $root )
    {
        foreach ( $this->node->nodes as $node )
        {
            $root->appendChild( new DOMText( $node->token->content ) );
        }
    }

    /**
     * Transform text role to docbook
     *
     * Create a docbook XML structure at the text roles position in the
     * document.
     *
     * @param DOMDocument $document
     * @param DOMElement $root
     * @return void
     */
    abstract public function toDocbook( DOMDocument $document, DOMElement $root );
}

?>
