<?php
/**
 * File containing the ezcDocumentDocbookToWikiItemizedListHandler class.
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
 * Visit itemized list / bullet lists.
 *
 * Visit itemized lists (bullet list) and maintain the correct indentation for
 * list items.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToWikiItemizedListHandler extends ezcDocumentDocbookToWikiBaseHandler
{
    /**
     * Current list indentation level.
     *
     * @var int
     */
    protected $level = 0;

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
        ++$this->level;

        foreach ( $node->childNodes as $child )
        {
            if ( ( $child->nodeType === XML_ELEMENT_NODE ) &&
                 ( $child->tagName === 'listitem' ) )
            {
                foreach ( $child->childNodes as $para )
                {
                    if ( $para->nodeType !== XML_ELEMENT_NODE )
                    {
                        continue;
                    }

                    if ( $para->tagName === 'para' )
                    {
                        $root .= str_repeat( '*', $this->level ) . ' ' .
                            trim( $converter->visitChildren( $para, '' ) ) . "\n\n";
                    }
                    else
                    {
                        $root = $converter->visitNode( $para, $root );
                    }
                }
            }
        }

        --$this->level;
        return $root;
    }
}

?>
