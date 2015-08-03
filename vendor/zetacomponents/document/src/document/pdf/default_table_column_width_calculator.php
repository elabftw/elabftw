<?php
/**
 * File containing the ezcDocumentPdfDefaultTableColumnWidthCalculator class
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
 * Table column width calculator
 *
 * Default implementation for a table column width calculator, which is 
 * responsible to estimate / guess / calculate sensible column width for a 
 * docbook table definition.
 *
 * Introspects the contents of a table and guesses based on included media and 
 * number of words in a cell what a reasonable column width might be.
 *
 * Since this implementation is mostly based on the count and length of words 
 * in one column, it might return unreasonably small column sizes for single 
 * columns. This might lead to columns, where not even a single characters fits 
 * in, which may cause problems while rendering.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfDefaultTableColumnWidthCalculator extends ezcDocumentPdfTableColumnWidthCalculator
{
    /**
     * Estimate column widths
     *
     * Should return an array with the column widths given as float numbers 
     * between 0 and 1, which all add together to 1.
     * 
     * @param DOMElement $table 
     * @return array
     */
    public function estimateWidths( DOMElement $table )
    {
        $xpath = new DOMXPath( $table->ownerDocument );
        $columns = array();
        foreach ( $xpath->query( './*/*/*[local-name() = "row"] | ./*/*[local-name() = "row"]', $table ) as $rowNr => $row )
        {
            foreach ( $xpath->query( './*[local-name() = "entry"]', $row ) as $cellNr => $cell )
            {
                $columns[$cellNr][$rowNr]['text']  = trim( strip_tags( $cell->textContent ) );
                $columns[$cellNr][$rowNr]['media'] = $cell->getElementsByTagName( 'mediaobject' );
            }
        }

        // Calculate guess values for amount of text in cells
        $textFactors = array_fill( 0, count( $columns ), 0. );
        foreach ( $columns as $nr => $column )
        {
            foreach ( $column as $cell )
            {
                $words = preg_split( '(\s+)', $cell['text'] );
                $count = count( $words );
                array_map( 'strlen', $words );

                $textFactors[$nr] += $count + max( $words ) / $count;
            }
        }

        // Normalize values
        $sum = array_sum( $textFactors );
        foreach ( $textFactors as $nr => $factor )
        {
            $textFactors[$nr] /= $sum;
        }

        $textFactors = array_map( 'sqrt', $textFactors );

        // Normalize values
        $sum = array_sum( $textFactors );
        foreach ( $textFactors as $nr => $factor )
        {
            $textFactors[$nr] /= $sum;
        }

        return $textFactors;
    }
}
?>
