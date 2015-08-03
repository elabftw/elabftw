<?php
/**
 * File containing the ezcDocumentXhtmlTablesFilter class
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
 * @access private
 */

/**
 * Filter, which tries to filter out tables, which do not have typical table
 * contents. Eg. are used for layout instead of content markup.
 *
 * The filter checks the number of cells which contain mostly text and when the
 * factor drops below a configured threshold the table is removed from the
 * content tree.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlTablesFilter extends ezcDocumentXhtmlBaseFilter
{
    /**
     * Percent of cells which are required to contain textual content.
     *
     * @var float
     */
    protected $threshold = .8;

    /**
     * Construct tables filter
     *
     * Construct the tables filter with the percentage values of cells with
     * textual contents requierd for each table not to be deleted. It defaults
     * to .8 (80%).
     *
     * @param float $threshold
     * @return void
     */
    public function __construct( $threshold = .8 )
    {
        $this->threshold = (float) $threshold;
    }

    /**
     * Filter XHtml document
     *
     * Filter for the document, which may modify / restructure a document and
     * assign semantic information bits to the elements in the tree.
     *
     * @param DOMDocument $document
     * @return DOMDocument
     */
    public function filter( DOMDocument $document )
    {
        $xpath = new DOMXPath( $document );

        // Find all tables
        $tables = $xpath->query( '//*[local-name() = "table"]' );

        foreach ( $tables as $table )
        {
            // Ignore tables, which again contain tables, as these most
            // probably contain the website content somehow.
            if ( $xpath->query( './/*[local-name() = "table"]', $table )->length > 0 )
            {
                continue;
            }

            // Extract all cells from the table and check what they contain
            $cells = $xpath->query( './/*[local-name() = "td"] | .//*[local-name() = "th"]', $table );
            $cellCount = $cells->length;
            $cellContentCount = 0;

            foreach ( $cells as $cell )
            {
                $cellContentCount += (int) $this->cellHasContent( $cell );
            }

            // Completely remove table, if it does not meet the configured
            // expectations
            if ( ( $cellContentCount / $cellCount ) < $this->threshold )
            {
                $table->parentNode->removeChild( $table );
                continue;
            }

            // Tables with only one column are most probably also used only for
            // layout. We remove them, too.
            if ( $xpath->query( './/*[local-name() = "tr"]', $table )->length >= $cellCount )
            {
                $table->parentNode->removeChild( $table );
                continue;
            }
        }
    }

    /**
     * Check if table has proper content
     *
     * Retrun true, if the cell has proper textual content.
     *
     * Extensions of this method may check for patterns in the table contents
     * for better detection of the table semantics.
     *
     * @param DOMElement $cell
     * @return bool
     */
    protected function cellHasContent( DOMElement $cell )
    {
        return (bool) strlen( trim( $cell->textContent ) );
    }
}

?>
