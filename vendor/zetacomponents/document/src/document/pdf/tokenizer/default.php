<?php
/**
 * File containing the ezcDocumentPdfDefaultTokenizer class
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
 * Tokenizer implementation for common texts, using whitespaces as word
 * seperators.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfDefaultTokenizer extends ezcDocumentPdfTokenizer
{
    /**
     * Split string into words
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
        $string = preg_replace( '(\\s+)', ' ', $string );
        $words  = preg_split( '(( ))', $string, -1, PREG_SPLIT_DELIM_CAPTURE );

        // Convert spaces to the marker constant
        foreach ( $words as $key => $word )
        {
            if ( $word === '' )
            {
                unset( $words[$key] );
            }
            else if ( $word === ' ' )
            {
                $words[$key] = self::SPACE;
            }
        }

        return array_values( $words );
    }
}

?>
