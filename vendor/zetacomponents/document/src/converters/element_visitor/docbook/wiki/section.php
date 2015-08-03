<?php
/**
 * File containing the ezcDocumentDocbookToWikiSectionHandler class.
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
 * Visit docbook sections
 *
 * Updates the docbook sections, which give us information about the depth
 * in the document, and may also be reference targets.
 *
 * Also visits title elements, which are commonly the first element in sections
 * and define section titles, which are converted to HTML header elements of
 * the respective level of indentation
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToWikiSectionHandler extends ezcDocumentDocbookToWikiBaseHandler
{
    /**
     * Current level of indentation in the docbook document.
     *
     * @var int
     */
    protected $level = -1;

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
        // Reset indenteation level, ever we reach a new section
        ezcDocumentDocbookToWikiConverter::$indentation = 0;

        if ( $node->tagName === 'title' )
        {
            // Get actual title string by recursing into the title node
            $title = trim( $converter->visitChildren( $node, '' ) );

            return $root . sprintf( "\n%s %s\n\n",
                str_repeat( "=", $this->level + 1 ),
                $title
            );
        }
        else
        {
            ++$this->level;

            // Recurse
            $root = $converter->visitChildren( $node, $root );

            // Reduce header level back to original state after recursion
            --$this->level;
        }

        return $root;
    }
}

?>
