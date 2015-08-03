<?php
/**
 * File containing the ezcDocumentEzXmlToDocbookTableRowHandler class.
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
 * Visit eZXml table row.
 *
 * Visit tables, which are quite similar to HTML tables and transform to
 * classic Docbook tables.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentEzXmlToDocbookTableRowHandler extends ezcDocumentElementVisitorHandler
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
        $element = $root->ownerDocument->createElement( 'row' );

        // Handle attributes
        $xpath = new DOMXPath( $node->ownerDocument );
        $isHeader = (bool) $xpath->query( './*[local-name() = "th"]', $node )->length;

        if ( $root->tagName === 'table' )
        {
            $tablePart = $root->ownerDocument->createElement( $isHeader ? 'thead' : 'tbody' );
            $root->appendChild( $tablePart );
            $root = $tablePart;
        }
        elseif ( $root->tagName !== ( $isHeader ? 'thead' : 'tbody' ) )
        {
            $tablePart = $root->ownerDocument->createElement( $isHeader ? 'thead' : 'tbody' );
            $root->parentNode->appendChild( $tablePart );
            $root = $tablePart;
        }

        $root->appendChild( $element );

        // Recurse
        $converter->visitChildren( $node, $element );
        return $root;
    }
}

?>
