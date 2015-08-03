<?php
/**
 * File containing the ezcDocumentDocbookToRstBlockquoteHandler class.
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
 * Visit blockquotes
 *
 * Visit blockquotes and transform them their respective HTML elements,
 * including custom markup for attributions, as there is no defined element
 * in HTML for them.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToRstBlockquoteHandler extends ezcDocumentDocbookToRstBaseHandler
{
    /**
     * Handle a node
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
        // Locate optional attribution elements, and transform them below the
        // recursive quote visiting.
        $xpath = new DOMXPath( $node->ownerDocument );
        $attributionNodes = $xpath->query( '*[local-name() = "attribution"]', $node );
        $attributions = array();
        foreach ( $attributionNodes as $attribution )
        {
            $attributions[] = $attribution->cloneNode( true );
            $attribution->parentNode->removeChild( $attribution );
        }

        // Recursively decorate blockquote, after all attribution nodes are
        // removed
        ezcDocumentDocbookToRstConverter::$indentation += 4;
        $root = $converter->visitChildren( $node, $root );

        // Append attribution nodes, if any
        foreach ( $attributions as $attribution )
        {
            $converter->setSkipPostDecoration( true );
            $attributionLine = '-- ' . trim( $converter->visitChildren( $attribution, '' ) );
            $converter->setSkipPostDecoration( false );
            $root .= ezcDocumentDocbookToRstConverter::wordWrap( $attributionLine ) . "\n\n";
        }

        ezcDocumentDocbookToRstConverter::$indentation -= 4;
        return $root;
    }
}

?>
