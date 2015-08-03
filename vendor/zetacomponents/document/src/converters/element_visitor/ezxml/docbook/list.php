<?php
/**
 * File containing the ezcDocumentEzXmlToDocbookListHandler class.
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
 * Visit eZXml lists.
 *
 * Lists are children of paragraphs in eZXml, but are part of the section nodes
 * in docbook. The containing paragraph needs to be split up and the list node
 * moved to the top level node.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentEzXmlToDocbookListHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * Handle a node.
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     *
     * @param ezcDocumentElementVisitorConverter $converter
     * @param DOMElement $node
     * @param mixed $root
     * @return mixed
     */
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $element = $root->ownerDocument->createElement( $node->tagName === 'ul' ? 'itemizedlist' : 'orderedlist' );
        $root->parentNode->appendChild( $element );

        // If there are any siblings, put them into a new paragraph node,
        // "below" the list node.
        if ( $node->nextSibling )
        {
            $newParagraph = $node->ownerDocument->createElement( 'paragraph' );

            do {
                $newParagraph->appendChild( $node->nextSibling->cloneNode( true ) );
                $node->parentNode->removeChild( $node->nextSibling );
            } while ( $node->nextSibling );

            $node->parentNode->parentNode->appendChild( $newParagraph );
        }

        // Recurse
        $converter->visitChildren( $node, $element );
        return $root;
    }
}

?>
