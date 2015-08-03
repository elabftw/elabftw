<?php
/**
 * File containing the ezcDocumentRstFigureDirective class
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
 * Visitor for RST image directives
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentRstFigureDirective extends ezcDocumentRstImageDirective
{
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
    public function toDocbook( DOMDocument $document, DOMElement $root )
    {
        parent::toDocbook( $document, $root );

        $text = '';
        foreach ( $this->node->nodes as $node )
        {
            $text .= $node->token->content;
        }
        $text = trim( $text );

        if ( !empty( $text ) )
        {
            $media = $root->getElementsBytagName( 'mediaobject' )->item( 0 );
            $caption = $document->createElement( 'caption' );
            $media->appendChild( $caption );

            $paragraph = $document->createElement( 'para', htmlspecialchars( $text ) );
            $caption->appendChild( $paragraph );
        }
    }

    /**
     * Transform directive to HTML
     *
     * Create a XHTML structure at the directives position in the document.
     *
     * @param DOMDocument $document
     * @param DOMElement $root
     * @return void
     */
    public function toXhtml( DOMDocument $document, DOMElement $root )
    {
        $box = $document->createElement( 'div' );
        $box->setAttribute( 'class', 'figure' );
        $root->appendChild( $box );

        parent::toXhtml( $document, $box );

        $text = '';
        foreach ( $this->node->nodes as $node )
        {
            $text .= $node->token->content;
        }
        $text = trim( $text );

        $paragraph = $document->createElement( 'p', htmlspecialchars( $text ) );
        $box->appendChild( $paragraph );
    }
}

?>
