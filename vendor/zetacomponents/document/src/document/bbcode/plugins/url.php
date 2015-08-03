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
 * Visitor for bbcode url tags
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentBBCodeUrlPlugin extends ezcDocumentBBCodePlugin
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
        if ( $node->token->parameters !== null )
        {
            // The actual URL is a parameter
            $url = $root->ownerDocument->createElement( 'ulink' );
            $url->setAttribute( 'url', $node->token->parameters );
            $root->appendChild( $url );

            foreach ( $node->nodes as $child )
            {
                $visitor->visitNode( $url, $child );
            }
        }
        else
        {
            // The URL is the contained text of the link
            $url = $root->ownerDocument->createElement( 'ulink' );
            $root->appendChild( $url );

            foreach ( $node->nodes as $child )
            {
                $visitor->visitNode( $url, $child );
            }

            $url->setAttribute( 'url', $url->textContent );
        }
    }
}

?>
