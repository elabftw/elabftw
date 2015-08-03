<?php
/**
 * File containing the ezcDocumentPdfTokenizer class
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
 * Abstract base class for tokenizer implementations.
 *
 * Tokenizers are used to split a series of words (sentences) into single
 * words, which can be rendered split by spaces.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentPdfTokenizer
{
    /**
     * Constant indicating a breaking point, including a rendered space.
     */
    const SPACE = 0;

    /**
     * Constant indicating a possible breaking point without rendering a space
     * character.
     */
    const WRAP = 1;

    /**
     * Constant indicating a forced breaking point without rendering a space
     * character.
     */
    const FORCED = 2;

    /**
     * Split string into words
     *
     * This function takes a string and splits it into words. There are
     * different mechanisms which indicate possible splitting points in the
     * resulting word stream:
     *
     * - self:SPACE: The renderer might render a space
     * - self:WRAP: The renderer might wrap the line at this position, but will
     *   not render spaces, might as well just be omitted.
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
    abstract public function tokenize( $string );
}

?>
