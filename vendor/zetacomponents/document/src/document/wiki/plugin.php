<?php
/**
 * File containing the ezcDocumentWikiPlugin class
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
 * Visitor for wiki directives
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentWikiPlugin
{
    /**
     * Current directive wiki AST node.
     *
     * @var ezcDocumentWikiPluginNode
     */
    protected $node;

    /**
     * Complete wiki abstract syntax tree, if this is necessary to render the
     * directive.
     *
     * @var ezcDocumentWikiDocumentNode
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
     * @var ezcDocumentWikiVisitor
     */
    protected $visitor;

    /**
     * Construct directive from AST and node
     *
     * @param ezcDocumentWikiDocumentNode $ast
     * @param string $path
     * @param ezcDocumentWikiPluginNode $node
     * @return void
     */
    public function __construct( ezcDocumentWikiDocumentNode $ast, $path, ezcDocumentWikiPluginNode $node )
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
     * @param ezcDocumentWikiVisitor $visitor
     * @return void
     */
    public function setSourceVisitor( ezcDocumentWikiVisitor $visitor )
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
}

?>
