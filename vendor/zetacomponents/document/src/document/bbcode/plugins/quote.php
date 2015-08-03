<?php
/**
 * File containing the ezcDocumentBBCodePlugin class
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
 * Visitor for bbcode emphasis tags
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentBBCodeQuotePlugin extends ezcDocumentBBCodePlugin
{
    /**
     * Convert a BBCode tag into Docbook
     *
     * Convert the given node into a Docbook structure, in the given root. For 
     * child elements in the node you may call the visitNode() method of the 
     * provided visitor.
     *
     * @param ezcDocumentBBCodeVisitor $visitor 
     * @param DOMElement $root 
     * @param ezcDocumentBBCodeNode $node 
     * @return void
     */
    public function toDocbook( ezcDocumentBBCodeVisitor $visitor, DOMElement $root, ezcDocumentBBCodeNode $node )
    {
        $quote = $root->ownerDocument->createElement( 'blockquote' );
        $root->appendChild( $quote );

        $attribution = null;
        if ( !empty( $node->token->parameters ) )
        {
            if ( !preg_match( '(^"(?P<attribution>.*)$")', $node->token->parameters, $match ) )
            {
                $visitor->triggerError( E_NOTICE,
                    'Attribution is required to be set in quotes.',
                    $node->token->line, $node->token->position
                );
                $attribution = $node->token->parameters;
            }
            else
            {
                $attribution = $match['attribution'];
            }
        }

        if ( $attribution )
        {
            $attribution = $root->ownerDocument->createElement( 'attribution', htmlspecialchars( $attribution ) );
            $quote->appendChild( $attribution );
        }

        $para = $root->ownerDocument->createElement( 'para' );
        $quote->appendChild( $para );

        foreach ( $node->nodes as $child )
        {
            $visitor->visitNode( $para, $child );
        }
    }
}

?>
