<?php
/**
 * File containing the ezcDocumentDocbookToWikiTableHandler class.
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
 * Visit tables.
 *
 * The RST table rendering algorithm tries losely to fit a table in the
 * provided document dimensions. This may not always work for over long words,
 * or if the table cells contain literal blocks which can not be wrapped.
 *
 * For this the algorithm estiamtes the available width per column, equally
 * distributes this available width over all columns (which might be far from
 * optimal), and extends the total table width if some cell content exceeds the
 * column width.
 *
 * The initial table cell estiation happens inside the function
 * estimateColumnWidths() which you might want to extend to fit your needs
 * better.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToWikiTableHandler extends ezcDocumentDocbookToWikiBaseHandler
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
        $rows = $node->getElementsByTagName( 'row' );

        foreach ( $rows as $row )
        {
            $header = ( $row->parentNode->tagName === 'thead' );
            $cells = $row->getElementsByTagName( 'entry' );
            foreach ( $cells as $cell )
            {
                $root .= ( $header ? '|= ' : '| ' ) .
                    preg_replace( '(\s+)', ' ', trim( $converter->visitChildren( $cell, '' ) ) );
                $root .= ' ';
            }
            $root .= "\n";
        }

        $root .= "\n";
        return $root;
    }
}

?>
