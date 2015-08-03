<?php
/**
 * File containing the ezcDocumentRstDirective class
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
 * Visitor for RST directives
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentRstDirective
{
    /**
     * Current directive RST AST node.
     *
     * @var ezcDocumentRstDirectiveNode
     */
    protected $node;

    /**
     * Complete RST abstract syntax tree, if this is necessary to render the
     * directive.
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
     * Construct directive from AST and node
     *
     * @param ezcDocumentRstDocumentNode $ast
     * @param string $path
     * @param ezcDocumentRstDirectiveNode $node
     * @return void
     */
    public function __construct( ezcDocumentRstDocumentNode $ast, $path, ezcDocumentRstDirectiveNode $node )
    {
        $this->ast  = $ast;
        $this->path = $path;
        $this->node = $node;
    }

    /**
     * Set the calling vaisitor
     *
     * Pass the visitor which called the rendering function on the directive
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
     * Transform directive to docbook
     *
     * Create a docbook XML structure at the directives position in the
     * document.
     *
     * @param DOMDocument $document
     * @param DOMElement $root
     * @return void
     */
    abstract public function toDocbook( DOMDocument $document, DOMElement $root );

    /**
     * Parse directive token list with RST parser
     *
     * This method is intended to parse the token list, provided for the RST 
     * contents using the standard RST parser. It will be visited afterwards by 
     * the provided RST-visitor implementation.
     *
     * The method returns the created document as a DOMDocument. You normally 
     * need to use DOMDocument::importNode to embed the conatined nodes in your 
     * target document.
     * 
     * @param array $tokens 
     * @param ezcDocumentRstVisitor $visitor 
     * @return DOMDocument
     */
    protected function parseTokens( array $tokens, ezcDocumentRstVisitor $visitor )
    {
        $parser = new ezcDocumentRstParser();
        $ast = $parser->parse( $tokens );

        $doc = $visitor->visit( $ast, $this->path );
        return $doc;
    }
}

?>
