<?php
/**
 * File containing the ezcDocumentPdfHyphenator class
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
 * Default hyphenation implementation, which does no word splitting at all.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcTestDocumentPdfHyphenator extends ezcDocumentPdfHyphenator
{
    /**
     * Split word into hypens
     *
     * Takes a word as a string and should return an array containing arrays of
     * two words, which each represent a possible split of a word. The german
     * word "Zuckerstück" for example changes its hyphens depending on the
     * splitting point, so the return value would look like:
     *
     * <code>
     *  array(
     *      array( 'Zuk-', 'kerstück' ),
     *      array( 'Zucker-', 'stück' ),
     *  )
     * </code>
     *
     * You should always also include the concatenation character in the split
     * words, since it might change depending on the used language.
     * 
     * @param mixed $word 
     * @return void
     */
    public function splitWord( $word )
    {
        $splits = array();
        for ( $i = 1; $i < iconv_strlen( $word, 'UTF-8' ); ++$i )
        {
            $splits[] = array(
                iconv_substr( $word, 0, $i ) . '-',
                iconv_substr( $word, $i )
            );
        }
        return $splits;
    }
}
?>
