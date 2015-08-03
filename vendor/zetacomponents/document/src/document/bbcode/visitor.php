<?php
/**
 * File containing the ezcDocumentBBCodeVisitor class
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
 * Abstract visitor base for BBCode documents represented by the parser AST.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentBBCodeVisitor implements ezcDocumentErrorReporting
{
    /**
     * BBCode document
     *
     * @var ezcDocumentBBCode
     */
    protected $bbcode;

    /**
     * Reference to the AST root node.
     *
     * @var ezcDocumentBBCodeDocumentNode
     */
    protected $ast;

    /**
     * Aggregated minor errors during document processing.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Create visitor from BBCode document handler.
     *
     * @param ezcDocumentBBCode $document
     * @param string $path
     * @return void
     */
    public function __construct( ezcDocumentBBCode $document, $path )
    {
        $this->bbcode = $document;
        $this->path   = $path;
    }

    /**
     * Trigger visitor error
     *
     * Emit a vistitor error, and convert it to an exception depending on the
     * error reporting settings.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param int $position
     * @return void
     */
    public function triggerError( $level, $message, $file = null, $line = null, $position = null )
    {
        if ( $level & $this->bbcode->options->errorReporting )
        {
            throw new ezcDocumentVisitException( $level, $message, $file, $line, $position );
        }
        else
        {
            // If the error should not been reported, we aggregate it to maybe
            // display it later.
            $this->errors[] = new ezcDocumentVisitException( $level, $message, $file, $line, $position );
        }
    }

    /**
     * Return list of errors occured during visiting the document.
     *
     * May be an empty array, if on errors occured, or a list of
     * ezcDocumentVisitException objects.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

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
        $this->ast = $ast;
    }

    /**
     * Visit text node
     *
     * @param DOMNode $root
     * @param ezcDocumentBBCodeNode $node
     * @return void
     */
    protected function visitText( DOMNode $root, ezcDocumentBBCodeNode $node )
    {
        $root->appendChild(
            new DOMText( preg_replace( '(\\s+)', ' ', $node->token->content ) )
        );
    }
}

?>
