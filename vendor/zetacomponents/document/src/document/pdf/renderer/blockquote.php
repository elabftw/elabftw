<?php
/**
 * File containing the ezcDocumentPdfBlockquoteRenderer class.
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
 * Renders a blockquote.
 *
 * Renders a blockquote and its attributions. A blockquote is basically an 
 * indented common paragraph, with the styling given by the used CSS file.
 *
 * The annotations precede the actual quote in Docbook, but will be rendered 
 * below the quote by this renderer.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfBlockquoteRenderer extends ezcDocumentPdfBlockRenderer
{
    /**
     * Process to render block contents.
     * 
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $block 
     * @param ezcDocumentPdfMainRenderer $mainRenderer 
     * @return void
     */
    protected function process( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $block, ezcDocumentPdfMainRenderer $mainRenderer )
    {
        $childNodes   = $block->childNodes;
        $nodeCount    = $childNodes->length;
        $attributions = array();

        for ( $i = 0; $i < $nodeCount; ++$i )
        {
            $child = $childNodes->item( $i );
            if ( $child->nodeType !== XML_ELEMENT_NODE )
            {
                continue;
            }

            // Default to docbook namespace, if no namespace is defined
            $namespace = $child->namespaceURI === null ? 'http://docbook.org/ns/docbook' : $child->namespaceURI;
            if ( ( $namespace === 'http://docbook.org/ns/docbook' ) &&
                 ( $child->tagName === 'attribution' ) )
            {
                $attributions[] = $child;
                continue;
            }

            $mainRenderer->processNode( $child );
        }

        // Render attributions below the actual quotes
        $textRenderer = new ezcDocumentPdfTextBoxRenderer( $this->driver, $this->styles );
        foreach ( $attributions as $attribution )
        {
            $textRenderer->renderNode( $page, $hyphenator, $tokenizer, $attribution, $mainRenderer );
        }
    }
}

?>
