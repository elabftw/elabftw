<?php
/**
 * File containing the ezcDocumentBBCodeDocbookVisitor class
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
 * Docbook visitor for the BBCode AST.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentBBCodeDocbookVisitor extends ezcDocumentBBCodeVisitor
{
    /**
     * Mapping of AST nodes to the visitor methods, which are sued to transform 
     * the respective node into Docbook
     * 
     * @var array
     */
    protected $visitMapping = array(
        'ezcDocumentBBCodeParagraphNode'      => 'visitParagraph',
        'ezcDocumentBBCodeTextNode'           => 'visitText',
        'ezcDocumentBBCodeTagNode'            => 'visitTag',
        'ezcDocumentBBCodeInlineLiteralNode'  => 'visitInlineLiteral',
        'ezcDocumentBBCodeLiteralBlockNode'   => 'visitLiteralBlock',
        'ezcDocumentBBCodeBulletListNode'     => 'visitBulletList',
        'ezcDocumentBBCodeEnumeratedListNode' => 'visitEnumeratedList',
    );

    /**
     * DOM document
     *
     * @var DOMDocument
     */
    protected $document;

    /**
     * Docarate BBCode AST
     *
     * Visit the BBCode abstract syntax tree.
     *
     * @param ezcDocumentBBCodeDocumentNode $ast
     * @return mixed
     */
    public function visit( ezcDocumentBBCodeDocumentNode $ast )
    {
        // Create article from AST
        $imp = new DOMImplementation();
        $dtd = $imp->createDocumentType( 'article', '-//OASIS//DTD DocBook XML V4.5//EN', 'http://www.oasis-open.org/docbook/xml/4.5/docbookx.dtd' );
        $this->document = $imp->createDocument( 'http://docbook.org/ns/docbook', '', $dtd );
        $this->document->formatOutput = true;

//        $root = $this->document->createElement( 'article' );
        $root = $this->document->createElementNs( 'http://docbook.org/ns/docbook', 'article' );
        $this->document->appendChild( $root );

        // Visit all childs of the AST root node.
        foreach ( $ast->nodes as $node )
        {
            $this->visitNode( $root, $node );
        }

        return $this->document;
    }

    /**
     * Visit single AST node
     *
     * Visit a single AST node, may be called for each node found anywhere
     * as child. The current position in the DOMDocument is passed by a
     * reference to the current DOMNode, which is operated on.
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    public function visitNode( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        // Iterate over available visitors and use them to visit the nodes.
        foreach ( $this->visitMapping as $class => $method )
        {
            if ( $node instanceof $class )
            {
                return $this->$method( $root, $node );
            }
        }

        // We could not find any valid visitor.
        throw new ezcDocumentMissingVisitorException( get_class( $node ), $node->token->line, $node->token->position );
    }

    /**
     * Visit paragraph markup
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitParagraph( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $para = $this->document->createElement( 'para' );
        $root->appendChild( $para );

        foreach ( $node->nodes as $child )
        {
            $this->visitNode( $para, $child );
        }
    }

    /**
     * Visit simple BBCode tag
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitTag( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        if ( !isset( $this->bbcode->options->tags[$node->token->content] ) )
        {
            $this->triggerError( E_WARNING,
                "Missing plugin for tag [{$node->token->content}].",
                $node->token->line, $node->token->position
            );
        }
        else
        {
            $this->bbcode->options->tags[$node->token->content]->toDocbook( $this, $root, $node );
        }
    }

    /**
     * Visit inlien literal markup
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitInlineLiteral( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $literal = $this->document->createElement( 'literal', htmlspecialchars( $node->token->content ) );
        $root->appendChild( $literal );
    }

    /**
     * Visit literal block markup
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitLiteralBlock( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $literal = $this->document->createElement( 'literallayout', htmlspecialchars( $node->token->content ) );
        $root->appendChild( $literal );
    }

    /**
     * Visit list items in a list
     * 
     * @param DOMNode $list 
     * @param ezcDocumentBBCodeListNode $node 
     * @return void
     */
    protected function visitListItems( DOMNode $list, ezcDocumentBBCodeListNode $node )
    {
        foreach ( $node->nodes as $child )
        {
            if ( !$child instanceof ezcDocumentBBCodeListItemNode )
            {
                continue;
            }

            $item = $this->document->createElement( 'listitem' );
            $list->appendChild( $item );

            foreach ( $child->nodes as $grandChild )
            {
                $this->visitNode( $item, $grandChild );
            }
        }
    }

    /**
     * Visit bullet list
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitBulletList( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $list = $this->document->createElement( 'itemizedlist' );
        $root->appendChild( $list );

        $this->visitListItems( $list, $node );
    }

    /**
     * Visit enumerated list
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitEnumeratedList( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $list = $this->document->createElement( 'orderedlist' );
        $root->appendChild( $list );

        switch ( $node->token->parameters )
        {
            case 'a':
                $list->setAttribute( 'numeration', 'loweralpha' );
                break;

            case '1':
                $list->setAttribute( 'numeration', 'arabic' );
                break;
        }

        $this->visitListItems( $list, $node );
    }
}

?>
