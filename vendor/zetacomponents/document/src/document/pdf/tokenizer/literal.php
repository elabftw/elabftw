<?php
/**
 * File containing the ezcDocumentPdfLiteralTokenizer class.
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
 * Tokenizer implementation for literal blocks, preserving whitespaces.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfLiteralTokenizer extends ezcDocumentPdfTokenizer
{
    /**
     * Split string into words.
     *
     * This function takes a string and splits it into words. There are
     * different mechanisms which indicate possible splitting points in the
     * resulting word stream:
     *
     * - self:SPACE: The renderer might render a space
     * - self:WRAP: The renderer might wrap the line at this position, but will
     *   not render spaces.
     *
     * A possible splitting of an english sentence might look like:
     *
     * <code>
     *  array(
     *      'Hello',
     *      self:SPACE,
     *      'world!',
     *  );
     * </code>
     *
     * Non breaking spaces should not be splitted into multiple words, so there
     * will be no break applied.
     *
     * @param string $string
     * @return array
     */
    public function tokenize( $string )
    {
        $lines  = preg_split( '(\r\n|\r|\n)', $string );
        $tokens = array();
        foreach ( $lines as $nr => $line )
        {
            // @todo: Use a somehow configured tab-width instead of the default;
            $line  = $this->convertTabs( $line );
            $words = preg_split( '(( +))', $line, -1, PREG_SPLIT_DELIM_CAPTURE );

            // Convert spaces to the marker constant
            foreach ( $words as $key => $word )
            {
                if ( $word === '' )
                {
                    unset( $words[$key] );
                }
            }

            $tokens = array_merge(
                $tokens,
                $words,
                array( self::FORCED )
            );
        }

        return $tokens;
    }

    /**
     * Convert tabs to spaces.
     *
     * Convert all tabs to spaces, using a 8 spaces for a tab.
     *
     * @param string $string
     * @param int $tabwidth
     * @param int $offset
     * @return string
     */
    protected function convertTabs( $string, $tabwidth = 8, $offset = 0 )
    {
        while ( ( $position = strpos( $string, "\t" ) ) !== false )
        {
            $string =
                substr( $string, 0, $position ) .
                str_repeat( ' ', $tabwidth - ( ( $position + $offset ) % $tabwidth ) ) .
                substr( $string, $position + 1 );
        }

        return $string;
    }
}

?>
